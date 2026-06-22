<?php

namespace App\Http\Controllers;

use App\Http\Requests\Downloader\DownloaderSelectionRequest;
use App\Jobs\ProcessDownloadQueueExport;
use App\Models\DownloadQueue;
use App\Models\Filesystem;
use App\Services\DownloaderFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloaderController extends Controller
{
    public function __construct(
        private DownloaderFilterService $filters,
    ) {}

    public function verify(DownloaderSelectionRequest $request): JsonResponse
    {
        $membraneIds = $request->membraneIds();
        $methodIds = $request->methodIds();
        $structureIdentifiers = $request->structureIdentifiers();
        $proteinIds = $request->proteinIds();

        $passive = $this->filters->passiveQuery($membraneIds, $methodIds, $structureIdentifiers)->count();
        $active = $this->filters->activeQuery($structureIdentifiers, $proteinIds)->count();

        return response()->json([
            'data' => [
                'passive' => $passive,
                'active' => $active,
                'total' => $passive + $active,
            ],
        ]);
    }

    public function store(DownloaderSelectionRequest $request): JsonResponse
    {
        $selection = DownloadQueue::normalizeSelection([
            'membrane_ids' => $request->membraneIds(),
            'method_ids' => $request->methodIds(),
            'protein_ids' => $request->proteinIds(),
            'structure_identifiers' => $request->structureIdentifiers(),
        ]);
        $selectionHash = DownloadQueue::hashSelection($selection);
        $existingDownload = $this->findReusableDownload($selectionHash, $selection);

        if ($existingDownload) {
            return response()->json([
                'data' => [
                    'uuid' => $existingDownload->uuid,
                    'reused' => true,
                ],
            ]);
        }

        $download = DownloadQueue::create([
            'uuid' => (string) Str::uuid(),
            'state' => DownloadQueue::STATE_PENDING,
            'selection' => $selection,
            'selection_hash' => $selectionHash,
            'expires_at' => now()->addDays(DownloadQueue::EXPIRATION_DAYS),
        ]);

        ProcessDownloadQueueExport::dispatch($download);

        return response()->json([
            'data' => [
                'uuid' => $download->uuid,
                'reused' => false,
            ],
        ], 201);
    }

    public function show(DownloadQueue $download): JsonResponse
    {
        $restarted = $this->restartIfStalled($download);

        return response()->json([
            'data' => [
                'uuid' => $download->uuid,
                'state' => DownloadQueue::$states[$download->state] ?? 'unknown',
                'progress' => $download->progress,
                'error_message' => $download->error_message,
                'expires_at' => $download->expirationDate()->toISOString(),
                'expired' => $download->isExpired(),
                'restarted' => $restarted,
            ],
        ]);
    }

    public function download(DownloadQueue $download): StreamedResponse
    {
        abort_unless(
            $download->state === DownloadQueue::STATE_DONE
                && $download->file_path
                && ! $download->isExpired()
                && ! $download->files_deleted_at,
            404,
        );

        $filesystem = Filesystem::where('type', Filesystem::TYPE_EXPORTS)->first();

        abort_unless($filesystem && $filesystem->isInitialized(), 404);

        $disk = Storage::disk($filesystem->systemName);

        abort_unless($disk->exists($download->file_path), 404);

        return $disk->download($download->file_path, 'molmedb_export.zip');
    }

    /**
     * @param  array<string, mixed>  $selection
     */
    private function findReusableDownload(string $selectionHash, array $selection): ?DownloadQueue
    {
        $downloads = DownloadQueue::query()
            ->where('selection_hash', $selectionHash)
            ->where('expires_at', '>', now())
            ->whereNull('files_deleted_at')
            ->whereIn('state', [
                DownloadQueue::STATE_PENDING,
                DownloadQueue::STATE_RUNNING,
                DownloadQueue::STATE_DONE,
            ])
            ->latest('id')
            ->get();

        foreach ($downloads as $download) {
            if (DownloadQueue::normalizeSelection($download->selection ?? []) !== $selection) {
                continue;
            }

            if ($download->state !== DownloadQueue::STATE_DONE || $this->fileExists($download)) {
                return $download;
            }

            $download->forceFill([
                'expires_at' => now(),
            ])->save();
        }

        return null;
    }

    private function fileExists(DownloadQueue $download): bool
    {
        if (! $download->file_path) {
            return false;
        }

        $filesystem = Filesystem::where('type', Filesystem::TYPE_EXPORTS)->first();

        if (! $filesystem || ! $filesystem->isInitialized()) {
            return false;
        }

        return Storage::disk($filesystem->systemName)->exists($download->file_path);
    }

    private function restartIfStalled(DownloadQueue $download): bool
    {
        $runToken = DB::transaction(function () use ($download): ?string {
            $lockedDownload = DownloadQueue::query()
                ->lockForUpdate()
                ->findOrFail($download->getKey());

            if (! $lockedDownload->isStalled()) {
                return null;
            }

            $runToken = (string) Str::uuid();
            $lockedDownload->prepareForRestart($runToken);

            return $runToken;
        });

        $download->refresh();

        if ($runToken) {
            ProcessDownloadQueueExport::dispatch($download, $runToken);
        }

        return $runToken !== null;
    }
}
