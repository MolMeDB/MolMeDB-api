<?php

namespace App\Http\Controllers;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Http\Requests\Lab\LookupPublicationRequest;
use App\Http\Requests\Lab\PreviewLabUploadConfigRequest;
use App\Http\Requests\Lab\ReuploadLabUploadRequest;
use App\Http\Requests\Lab\StoreLabUploadRequest;
use App\Http\Requests\Lab\ValidateLabUploadConfigRequest;
use App\Models\Author;
use App\Models\Dataset;
use App\Models\File;
use App\Models\Filesystem;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Publication;
use App\Models\UploadQueue;
use App\Models\User;
use App\Services\UploadQueueFrontendConfigurator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\References\EuropePMC\Enums\Query\SortBy;
use Modules\References\EuropePMC\Enums\Query\SortOrder;
use Modules\References\EuropePMC\Enums\Sources;
use Modules\References\EuropePMC\EuropePMC;
use Modules\References\Models\Record;
use Throwable;

class LabUploadController extends Controller
{
    private const FRONTEND_STATE_LABELS = [
        UploadQueue::STATE_UPLOADED => 'Configuration required',
        UploadQueue::STATE_CONFIGURED => 'Ready to start upload',
        UploadQueue::STATE_PENDING => 'Pending upload',
        UploadQueue::STATE_RUNNING => 'Uploading',
        UploadQueue::STATE_DONE => 'Uploaded',
        UploadQueue::STATE_ERROR => 'Validation error',
        UploadQueue::STATE_CANCELED => 'Canceled',
    ];

    public function membranes(Request $request): JsonResponse
    {
        $query = trim($request->string('query')->toString());

        $records = Membrane::query()
            ->select(['id', 'abbreviation', 'name'])
            ->when($query !== '', function ($builder) use ($query) {
                $search = mb_strtolower($query);

                return $builder->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(abbreviation) LIKE ?', ['%'.$search.'%']);
            })
            ->orderBy('abbreviation')
            ->limit(25)
            ->get();

        return response()->json(['data' => $records]);
    }

    public function methods(Request $request): JsonResponse
    {
        $query = trim($request->string('query')->toString());

        $records = Method::query()
            ->select(['id', 'abbreviation', 'name'])
            ->when($query !== '', function ($builder) use ($query) {
                $search = mb_strtolower($query);

                return $builder->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(abbreviation) LIKE ?', ['%'.$search.'%']);
            })
            ->orderBy('abbreviation')
            ->limit(25)
            ->get();

        return response()->json(['data' => $records]);
    }

    public function publications(Request $request): JsonResponse
    {
        $query = trim($request->string('query')->toString());

        $records = Publication::query()
            ->select(['id', 'citation', 'title', 'doi', 'identifier', 'identifier_source'])
            ->when($query !== '', function ($builder) use ($query) {
                $search = mb_strtolower($query);

                return $builder->whereRaw('LOWER(citation) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(title) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(doi) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(identifier) LIKE ?', ['%'.$search.'%']);
            })
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return response()->json(['data' => $records]);
    }

    public function lookupPublications(LookupPublicationRequest $request): JsonResponse
    {
        $query = trim($request->string('query')->toString());
        $recordsByPmid = [];

        $localRecords = Publication::query()
            ->select(['identifier', 'identifier_source', 'citation', 'title', 'journal', 'year'])
            ->where('identifier_source', Sources::MED->value)
            ->where(function ($builder) use ($query) {
                $search = mb_strtolower($query);

                return $builder
                    ->whereRaw('LOWER(identifier) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(citation) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(title) LIKE ?', ['%'.$search.'%']);
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        foreach ($localRecords as $record) {
            if (! is_string($record->identifier) || trim($record->identifier) === '') {
                continue;
            }

            $pmid = trim($record->identifier);
            if (! ctype_digit($pmid)) {
                continue;
            }

            $recordsByPmid[$pmid] = [
                'provider' => 'europe_pmc',
                'pmid' => $pmid,
                'identifier_source' => Sources::MED->value,
                'citation' => $record->citation,
                'title' => $record->title,
                'journal' => $record->journal,
                'year' => $record->year,
                'is_local' => true,
            ];
        }

        try {
            $europePmc = new EuropePMC;
            $result = $europePmc->search($query, SortBy::SCORE, SortOrder::DESC, 1, 10);

            foreach (($result['records'] ?? []) as $record) {
                $mapped = $this->mapEuropePmcRecord($record);

                if (! isset($mapped['pmid']) || ! is_string($mapped['pmid']) || $mapped['pmid'] === '') {
                    continue;
                }

                $recordsByPmid[$mapped['pmid']] = $mapped;
            }
        } catch (Throwable) {
            // Best effort lookup only.
        }

        return response()->json([
            'data' => array_values($recordsByPmid),
        ]);
    }

    public function store(StoreLabUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $result = DB::transaction(function () use ($request, $validated, $user): array {
            $publication = $this->resolvePublication($validated);

            $datasetName = isset($validated['dataset_name']) && trim($validated['dataset_name']) !== ''
                ? trim($validated['dataset_name'])
                : sprintf('User upload [%s] by %s', now()->format('Y-m-d H:i'), $user->name);

            $comment = trim((string) ($validated['comment'] ?? ''));

            $dataset = Dataset::query()->create([
                'type' => (int) $validated['dataset_type'],
                'name' => $datasetName,
                'comment' => $comment !== '' ? $comment : null,
                'membrane_id' => (int) $validated['membrane_id'],
                'method_id' => (int) $validated['method_id'],
                'created_by' => $user->id,
            ]);

            $dataset->publications()->syncWithPivotValues(
                [$publication->id],
                ['model_type' => Dataset::class]
            );

            $uploadedFile = $request->file('file');
            $uploadDisk = $this->resolveUploadDisk();
            $uploadDirectory = UploadQueue::typeFolder((int) $validated['dataset_type']) ?? UploadQueue::typeFolder(Dataset::TYPE_PASSIVE);

            $storedFileName = sprintf(
                '[Dataset:%d]-%s-%s.%s',
                $dataset->id,
                Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'uploaded-data',
                Str::lower(Str::random(10)),
                $uploadedFile->getClientOriginalExtension() ?: 'dat',
            );

            $storedPath = $uploadedFile->storeAs($uploadDirectory, $storedFileName, $uploadDisk);

            if (! $storedPath) {
                throw ValidationException::withMessages([
                    'file' => 'Unable to save uploaded file. Try again.',
                ]);
            }

            $fileRecord = File::query()->create([
                'path' => $storedPath,
                'name' => basename($storedPath),
                'type' => (int) $validated['dataset_type'] === Dataset::TYPE_ACTIVE ? File::TYPE_UPLOAD_ACTIVE : File::TYPE_UPLOAD_PASSIVE,
                'storage' => $uploadDisk,
                'hash' => md5_file($uploadedFile->getRealPath()),
                'mime' => $uploadedFile->getMimeType(),
            ]);

            $uploadQueue = UploadQueue::query()->create([
                'type' => (int) $validated['dataset_type'],
                'state' => UploadQueue::STATE_UPLOADED,
                'file_id' => $fileRecord->id,
                'dataset_id' => $dataset->id,
                'user_id' => $user->id,
                'config' => [
                    'source_publication_id' => $publication->id,
                    'uploaded_file_name' => $uploadedFile->getClientOriginalName(),
                    'uploaded_at' => now()->toISOString(),
                ],
            ]);

            $uploadQueue->addStructuredLog(
                'Upload request created from frontend laboratory form.',
                UploadQueueLogContextEnums::INFO,
                UploadQueueLogTypeEnums::UPLOAD,
                UploadQueue::STATE_UPLOADED,
                [
                    'dataset_type' => $validated['dataset_type'],
                    'dataset_name' => $datasetName,
                    'method_id' => $validated['method_id'],
                    'membrane_id' => $validated['membrane_id'],
                    'publication_id' => $publication->id,
                    'publication_pmid' => $validated['publication_pmid'],
                    'comment' => $validated['comment'] ?? null,
                    'file_name' => $uploadedFile->getClientOriginalName(),
                    'file_size' => $uploadedFile->getSize(),
                    'file_mime' => $uploadedFile->getMimeType(),
                ],
                $user->id,
            );

            return [
                'dataset' => $dataset,
                'file' => $fileRecord,
                'upload_queue' => $uploadQueue,
                'publication' => $publication,
            ];
        });

        return response()->json([
            'message' => 'Upload request has been accepted and queued for configuration.',
            'data' => [
                'dataset_id' => $result['dataset']->id,
                'upload_queue_id' => $result['upload_queue']->id,
                'publication_id' => $result['publication']->id,
            ],
        ], 201);
    }

    public function myUploads(Request $request): JsonResponse
    {
        $records = UploadQueue::query()
            ->with(['file', 'dataset.membrane', 'dataset.method', 'dataset.publications'])
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json([
            'data' => $records->through(function (UploadQueue $record): array {
                $lastLog = $record->logs?->last();

                return [
                    'id' => $record->id,
                    'state' => $record->state,
                    'state_label' => self::FRONTEND_STATE_LABELS[$record->state] ?? UploadQueue::enumState($record->state),
                    'state_phase' => $this->statePhase($record->state),
                    'can_reupload' => $record->state === UploadQueue::STATE_ERROR,
                    'can_configure' => in_array($record->state, [UploadQueue::STATE_UPLOADED, UploadQueue::STATE_ERROR, UploadQueue::STATE_CONFIGURED], true),
                    'can_enqueue' => $record->state === UploadQueue::STATE_CONFIGURED &&
                        $record->hasValidConfig() &&
                        (bool) ($record->config['quick_validation_ok'] ?? false),
                    'can_revert' => $record->state === UploadQueue::STATE_PENDING,
                    'can_cancel' => in_array($record->state, [
                        UploadQueue::STATE_UPLOADED,
                        UploadQueue::STATE_ERROR,
                        UploadQueue::STATE_CONFIGURED,
                        UploadQueue::STATE_PENDING,
                    ], true),
                    'dataset' => [
                        'id' => $record->dataset?->id,
                        'name' => $record->dataset?->name,
                        'type' => $record->dataset ? Dataset::enumType((int) $record->dataset->type) : null,
                        'membrane' => $record->dataset?->membrane?->abbreviation,
                        'method' => $record->dataset?->method?->abbreviation,
                    ],
                    'publication' => $record->dataset?->publications?->first()?->citation,
                    'file' => [
                        'id' => $record->file?->id,
                        'name' => $record->file?->name,
                        'mime' => $record->file?->mime,
                    ],
                    'last_message' => $lastLog?->message,
                    'config' => [
                        'separator' => $record->config['separator'] ?? null,
                        'skip_first_row' => $record->config['skip_first_row'] ?? null,
                        'attributes' => $record->config['attributes'] ?? null,
                        'quick_validation_ok' => $record->config['quick_validation_ok'] ?? false,
                        'quick_validation_at' => $record->config['quick_validation_at'] ?? null,
                    ],
                    'logs' => $record->logs->map(fn ($log) => [
                        'message' => $log->message,
                        'context' => $log->context->value,
                        'type' => $log->type->value,
                        'state' => $log->state,
                        'payload' => $log->payload,
                        'timestamp' => $log->timestamp,
                        'user_id' => $log->user_id,
                    ])->values(),
                    'created_at' => $record->created_at?->toISOString(),
                    'updated_at' => $record->updated_at?->toISOString(),
                ];
            }),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function reupload(ReuploadLabUploadRequest $request, UploadQueue $record): JsonResponse
    {
        if (! $this->canManageUploadQueueRecord($request->user(), $record, 'reupload')) {
            abort(403);
        }

        if ((int) $record->state !== UploadQueue::STATE_ERROR) {
            throw ValidationException::withMessages([
                'record' => 'Only records in error state can be reuploaded.',
            ]);
        }

        $uploadedFile = $request->file('file');
        $uploadDisk = $this->resolveUploadDisk();
        $uploadDirectory = UploadQueue::typeFolder((int) $record->type) ?? UploadQueue::typeFolder(Dataset::TYPE_PASSIVE);

        $storedFileName = sprintf(
            '[Dataset:%d]-reupload-%s-%s.%s',
            $record->dataset_id,
            Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'uploaded-data',
            Str::lower(Str::random(10)),
            $uploadedFile->getClientOriginalExtension() ?: 'dat',
        );

        $storedPath = $uploadedFile->storeAs($uploadDirectory, $storedFileName, $uploadDisk);
        if (! $storedPath) {
            throw ValidationException::withMessages([
                'file' => 'Unable to save reuploaded file.',
            ]);
        }

        $newFileRecord = File::query()->create([
            'path' => $storedPath,
            'name' => basename($storedPath),
            'type' => (int) $record->type === Dataset::TYPE_ACTIVE ? File::TYPE_UPLOAD_ACTIVE : File::TYPE_UPLOAD_PASSIVE,
            'storage' => $uploadDisk,
            'hash' => md5_file($uploadedFile->getRealPath()),
            'mime' => $uploadedFile->getMimeType(),
        ]);

        $oldFile = $record->file;
        $config = is_array($record->config) ? $record->config : [];
        unset($config['validated_rows'], $config['validated_at'], $config['attributes'], $config['separator'], $config['skip_first_row']);

        $record->file_id = $newFileRecord->id;
        $record->config = $config;
        $record->save();

        $record->transitionToState(
            UploadQueue::STATE_UPLOADED,
            'File was reuploaded. Record returned to validation queue.',
            UploadQueueLogContextEnums::INFO,
            UploadQueueLogTypeEnums::REUPLOAD,
            [
                'new_file_name' => $newFileRecord->name,
                'new_file_id' => $newFileRecord->id,
                'old_file_id' => $oldFile?->id,
            ],
            Auth::id()
        );

        if ($oldFile) {
            $oldFile->delete();
        }

        return response()->json([
            'message' => 'File reuploaded successfully. Validation will run automatically.',
            'data' => [
                'id' => $record->id,
                'state' => $record->state,
                'state_label' => self::FRONTEND_STATE_LABELS[$record->state] ?? UploadQueue::enumState($record->state),
            ],
        ]);
    }

    public function configurePreview(
        PreviewLabUploadConfigRequest $request,
        UploadQueue $record,
        UploadQueueFrontendConfigurator $configurator,
    ): JsonResponse {
        if (! $this->canManageUploadQueueRecord($request->user(), $record, 'configure')) {
            abort(403);
        }

        $separator = (string) $request->input('separator', (string) ($record->config['separator'] ?? ','));
        $skipFirstRow = (int) $request->integer('skip_first_row', (int) ($record->config['skip_first_row'] ?? 1));
        $startLine = (int) $request->integer('start_line', 1);
        $limit = (int) $request->integer('limit', 5);

        $preview = $configurator->preview($record, $separator, $skipFirstRow, $startLine, $limit);
        if (! ($preview['ok'] ?? false)) {
            return response()->json([
                'message' => 'Unable to load file preview.',
                'errors' => $preview['errors'] ?? ['Unknown preview error.'],
            ], 422);
        }

        return response()->json([
            'data' => $preview,
        ]);
    }

    public function validateConfiguration(
        ValidateLabUploadConfigRequest $request,
        UploadQueue $record,
        UploadQueueFrontendConfigurator $configurator,
    ): JsonResponse {
        if (! $this->canManageUploadQueueRecord($request->user(), $record, 'configure')) {
            abort(403);
        }

        if (! in_array($record->state, [UploadQueue::STATE_UPLOADED, UploadQueue::STATE_ERROR, UploadQueue::STATE_CONFIGURED], true)) {
            throw ValidationException::withMessages([
                'record' => 'Configuration can be updated only for uploaded, configured or error records.',
            ]);
        }

        $result = $configurator->validateConfiguration(
            $record,
            (string) $request->string('separator')->toString(),
            (int) $request->integer('skip_first_row'),
            $request->input('attributes', []),
        );

        if (! ($result['ok'] ?? false)) {
            $record->addStructuredLog(
                'Quick frontend validation failed.',
                UploadQueueLogContextEnums::ERROR,
                UploadQueueLogTypeEnums::VALIDATION_RUN,
                $record->state,
                ['errors' => $result['errors'] ?? []],
                $record->user_id,
            );

            return response()->json([
                'message' => 'Validation failed. Please adjust configuration.',
                'errors' => ['config' => $result['errors'] ?? ['Unknown validation error.']],
                'warnings' => $result['warnings'] ?? [],
            ], 422);
        }

        $record->config = [
            ...(is_array($record->config) ? $record->config : []),
            ...($result['config'] ?? []),
            'detailed_validation_ok' => false,
            'detailed_validation_at' => null,
        ];
        $record->save();

        if ((int) $record->state !== UploadQueue::STATE_CONFIGURED) {
            $record->transitionToState(
                UploadQueue::STATE_CONFIGURED,
                'Configuration was saved. Record is ready to be started by user.',
                UploadQueueLogContextEnums::INFO,
                UploadQueueLogTypeEnums::STATE_CHANGE,
                null,
                $record->user_id
            );
        }

        $record->addStructuredLog(
            'Quick frontend validation passed and configuration was saved.',
            UploadQueueLogContextEnums::SUCCESS,
            UploadQueueLogTypeEnums::VALIDATION_RUN,
            UploadQueue::STATE_CONFIGURED,
            [
                'validated_rows' => $record->config['validated_rows'] ?? null,
            ],
            $record->user_id,
        );

        return response()->json([
            'message' => 'Configuration validated and saved successfully.',
            'data' => [
                'config' => $record->config,
                'warnings' => $result['warnings'] ?? [],
            ],
        ]);
    }

    public function enqueue(UploadQueue $record): JsonResponse
    {
        if (! $this->canManageUploadQueueRecord(Auth::user(), $record, 'enqueue')) {
            abort(403);
        }

        if ((int) $record->state !== UploadQueue::STATE_CONFIGURED) {
            throw ValidationException::withMessages([
                'record' => 'Only configured records can be sent to queue.',
            ]);
        }

        if (! $record->hasValidConfig() || ! (bool) ($record->config['quick_validation_ok'] ?? false)) {
            throw ValidationException::withMessages([
                'config' => 'Configuration must be validated before sending record to queue.',
            ]);
        }

        $config = is_array($record->config) ? $record->config : [];
        $config['detailed_validation_ok'] = false;
        $config['detailed_validation_at'] = null;
        $record->config = $config;
        $record->save();

        $record->transitionToState(
            UploadQueue::STATE_PENDING,
            'Record was sent to pending upload queue by user.',
            UploadQueueLogContextEnums::WARNING,
            UploadQueueLogTypeEnums::STATE_CHANGE,
            [
                'irreversible_notice' => true,
            ],
            $record->user_id
        );

        return response()->json([
            'message' => 'Record moved to pending queue. Detailed validation will run in scheduler.',
            'data' => [
                'id' => $record->id,
                'state' => $record->state,
                'state_label' => self::FRONTEND_STATE_LABELS[$record->state] ?? UploadQueue::enumState($record->state),
            ],
        ]);
    }

    public function cancel(UploadQueue $record): JsonResponse
    {
        if (! $this->canManageUploadQueueRecord(Auth::user(), $record, 'cancel')) {
            abort(403);
        }

        if ((int) $record->state === UploadQueue::STATE_CANCELED) {
            throw ValidationException::withMessages([
                'record' => 'Record is already marked for deletion.',
            ]);
        }

        if ((int) $record->state === UploadQueue::STATE_RUNNING) {
            throw ValidationException::withMessages([
                'record' => 'Running records cannot be canceled right now.',
            ]);
        }

        if (! in_array((int) $record->state, [
            UploadQueue::STATE_UPLOADED,
            UploadQueue::STATE_ERROR,
            UploadQueue::STATE_CONFIGURED,
            UploadQueue::STATE_PENDING,
        ], true)) {
            throw ValidationException::withMessages([
                'record' => 'This record cannot be canceled in current state.',
            ]);
        }

        $file = $record->file;
        $fileDeleted = false;

        if ($file) {
            $disk = is_string($file->storage) && trim($file->storage) !== '' ? $file->storage : null;
            if (! $disk) {
                throw ValidationException::withMessages([
                    'record' => 'Uploaded file storage is not configured.',
                ]);
            }

            if (Storage::disk($disk)->exists($file->path)) {
                $fileDeleted = Storage::disk($disk)->delete($file->path);
                if (! $fileDeleted) {
                    throw ValidationException::withMessages([
                        'record' => 'Uploaded file could not be deleted. Please try again.',
                    ]);
                }
            } else {
                $fileDeleted = true;
            }
        }

        $config = is_array($record->config) ? $record->config : [];
        $config['uploaded_file_deleted'] = $fileDeleted;
        $config['uploaded_file_deleted_at'] = now()->toISOString();
        $record->config = $config;
        $record->save();

        $record->transitionToState(
            UploadQueue::STATE_CANCELED,
            'Record was canceled by user and uploaded file was deleted.',
            UploadQueueLogContextEnums::WARNING,
            UploadQueueLogTypeEnums::STATE_CHANGE,
            [
                'file_id' => $file?->id,
                'file_name' => $file?->name,
                'file_deleted' => $fileDeleted,
            ],
            $record->user_id
        );

        return response()->json([
            'message' => 'Record canceled. Uploaded file was removed. This action cannot be reverted.',
            'data' => [
                'id' => $record->id,
                'state' => $record->state,
                'state_label' => self::FRONTEND_STATE_LABELS[$record->state] ?? UploadQueue::enumState($record->state),
            ],
        ]);
    }

    public function revert(UploadQueue $record): JsonResponse
    {
        if (! $this->canManageUploadQueueRecord(Auth::user(), $record, 'revert')) {
            abort(403);
        }

        if ((int) $record->state !== UploadQueue::STATE_PENDING) {
            throw ValidationException::withMessages([
                'record' => 'Only pending records can be reverted to configuration state.',
            ]);
        }

        $record->transitionToState(
            UploadQueue::STATE_CONFIGURED,
            'Record was reverted from pending to configuration state by user.',
            UploadQueueLogContextEnums::WARNING,
            UploadQueueLogTypeEnums::STATE_CHANGE,
            null,
            $record->user_id
        );

        return response()->json([
            'message' => 'Record reverted to configuration state.',
            'data' => [
                'id' => $record->id,
                'state' => $record->state,
                'state_label' => self::FRONTEND_STATE_LABELS[$record->state] ?? UploadQueue::enumState($record->state),
            ],
        ]);
    }

    private function resolveUploadDisk(): string
    {
        $disk = UploadQueue::disk();

        if (! is_string($disk) || $disk === '' || ! is_array(config('filesystems.disks.'.$disk))) {
            $publicFilesystemExists = Filesystem::query()
                ->where('type', Filesystem::TYPE_PUBLIC)
                ->exists();
            if (! $publicFilesystemExists) {
                return 'public';
            }

            return 'public';
        }

        return $disk;
    }

    private function canManageUploadQueueRecord(?User $user, UploadQueue $record, string $ability): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can($ability, $record);
    }

    private function statePhase(int $state): string
    {
        return match ($state) {
            UploadQueue::STATE_UPLOADED => 'configuration',
            UploadQueue::STATE_CONFIGURED => 'validating',
            UploadQueue::STATE_PENDING => 'pending',
            UploadQueue::STATE_RUNNING => 'processing',
            UploadQueue::STATE_DONE => 'done',
            UploadQueue::STATE_ERROR => 'error',
            UploadQueue::STATE_CANCELED => 'canceled',
            default => 'unknown',
        };
    }

    private function resolvePublication(array $validated): Publication
    {
        $pmid = isset($validated['publication_pmid']) && is_string($validated['publication_pmid'])
            ? trim($validated['publication_pmid'])
            : '';

        if ($pmid === '') {
            throw ValidationException::withMessages([
                'publication_pmid' => 'Publication PMID is required.',
            ]);
        }

        $publication = Publication::query()
            ->where('identifier', $pmid)
            ->where('identifier_source', Sources::MED->value)
            ->first();

        if ($publication) {
            return $publication;
        }

        $record = null;

        try {
            $record = (new EuropePMC)->detail($pmid, Sources::MED);
        } catch (Throwable) {
            $record = null;
        }

        if (! $record instanceof Record) {
            throw ValidationException::withMessages([
                'publication_pmid' => 'Publication was not found in Europe PMC.',
            ]);
        }

        $doi = $record->doi ? trim($record->doi) : null;
        $identifier = $record->id ? trim($record->id) : $pmid;
        $identifierSource = $record->source?->value ?? Sources::MED->value;

        $publication = new Publication;
        $publication->type = Publication::TYPE_COSMO;

        $publication->citation = Str::limit(
            $record->citation() ?: ($record->title ?? $doi ?? 'Unknown citation'),
            1024
        );
        $publication->title = $record->title ? Str::limit($record->title, 512) : null;
        $publication->doi = $doi;
        $publication->identifier = $identifier;
        $publication->identifier_source = $identifierSource;
        $publication->journal = $record->journal?->title;
        $publication->volume = $record->journal?->volume;
        $publication->issue = $record->journal?->issue;
        $publication->page = $record->pageInfo;
        $publication->year = is_numeric($record->journal?->yearOfPublication) ? (int) $record->journal?->yearOfPublication : null;
        $publication->published_at = null;
        if (is_string($record->journal?->dateOfPublication) && trim($record->journal->dateOfPublication) !== '') {
            try {
                $publication->published_at = Carbon::parse($record->journal->dateOfPublication)->toDateString();
            } catch (Throwable) {
                $publication->published_at = null;
            }
        }
        $publication->validated_at = now();
        $publication->save();

        if (is_array($record->authors) && count($record->authors) > 0) {
            foreach ($record->authors as $author) {
                $authorModel = Author::firstOrCreate([
                    'first_name' => $author->firstName,
                    'last_name' => $author->lastName,
                    'full_name' => $author->fullName,
                    'affiliation' => $author->affiliations && count($author->affiliations) > 0 ? $author->affiliations[0] : null,
                ]);

                $publication->authors()->syncWithoutDetaching([$authorModel->id]);
            }
        }

        return $publication->refresh();
    }

    private function mapEuropePmcRecord(Record $record): array
    {
        $identifier = is_string($record->id) ? trim($record->id) : '';
        $source = $record->source?->value;

        if ($source !== Sources::MED->value || $identifier === '' || ! ctype_digit($identifier)) {
            return [];
        }

        return [
            'provider' => 'europe_pmc',
            'pmid' => $identifier,
            'identifier_source' => Sources::MED->value,
            'citation' => $record->citation(),
            'title' => $record->title,
            'journal' => $record->journal?->title,
            'year' => $record->journal?->yearOfPublication,
            'is_local' => false,
        ];
    }
}
