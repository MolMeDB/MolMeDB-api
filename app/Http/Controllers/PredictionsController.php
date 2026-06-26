<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePredictionDatasetRequest;
use App\Http\Resources\PredictionDatasetResource;
use App\Http\Resources\PredictionResource;
use App\Http\Resources\PredictionStructureResource;
use App\Models\File;
use App\Models\Membrane;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionDataset;
use Modules\PredictionWorkers\Models\PredictionMembrane;
use Modules\PredictionWorkers\Models\PredictionStat;
use Modules\PredictionWorkers\Models\PredictionStructure;

class PredictionsController extends Controller
{
    public function serverStats(): array
    {
        $latestStat = PredictionStat::newest()->first();

        return [
            'data' => [
                'jobs' => [
                    'queued' => Prediction::where('state', Prediction::STATE_PREPARED)->count(),
                    'running' => Prediction::where('state', Prediction::STATE_RUNNING)->count(),
                    'completed' => Prediction::where('state', Prediction::STATE_FINISHED)->count(),
                    'failed' => Prediction::whereIn('state', Prediction::failedStates())->count(),
                ],
                'remote' => $latestStat ? [
                    'payload' => $latestStat->payload,
                    'fetched_at' => $latestStat->fetched_at?->toISOString(),
                    'server_url' => $latestStat->server_url,
                ] : null,
            ],
        ];
    }

    public function options(): array
    {
        $this->ensurePredictionMembranesAvailable();
        $availableMembraneIds = $this->availablePredictionMembraneRemoteIds();

        return [
            'data' => [
                'membranes' => PredictionMembrane::query()
                    ->select(['id', 'remote_id', 'name', 'abbreviation'])
                    ->whereIn('remote_id', $availableMembraneIds)
                    ->orderBy('abbreviation')
                    ->get()
                    ->map(fn (PredictionMembrane $membrane): array => [
                        'id' => $membrane->id,
                        'remote_id' => $membrane->remote_id,
                        'short_name' => $membrane->abbreviation,
                        'long_name' => $membrane->name,
                        'description' => null,
                        'show_more_link' => $membrane->remote_id
                            ? '/browse/membranes?id='.$membrane->remote_id
                            : null,
                    ])
                    ->values(),
                'methods' => collect(Prediction::remotePredictionMethodOptions())
                    ->map(fn (string $label, string $method): array => [
                        'id' => $method,
                        'short_name' => $label,
                        'long_name' => null,
                        'description' => null,
                    ])
                    ->values(),
                'priorities' => collect(Prediction::$enum_priorities)
                    ->map(fn (string $label, int $priority): array => [
                        'id' => $priority,
                        'short_name' => $label,
                    ])
                    ->values(),
            ],
        ];
    }

    public function storeDataset(StorePredictionDatasetRequest $request)
    {
        $this->ensurePredictionMembranesAvailable();
        $availableMembraneIds = $this->availablePredictionMembraneRemoteIds();

        $validated = $request->validated();
        $user = $request->user();
        $priority = $this->priorityValue($validated['priority']);
        $membraneIds = collect($validated['membranes'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $methodTypes = collect($validated['methods'])
            ->map(fn ($method): string => (string) $method)
            ->unique()
            ->values();
        $smiles = collect($validated['smiles'])
            ->map(fn ($smiles): string => trim((string) $smiles))
            ->filter(fn (string $smiles): bool => $smiles !== '' && ! str_starts_with($smiles, '#'))
            ->unique()
            ->values();

        if ($smiles->isEmpty()) {
            throw ValidationException::withMessages([
                'smiles' => ['At least one non-empty SMILES is required.'],
            ]);
        }

        $existingMembraneIds = PredictionMembrane::query()
            ->whereIn('id', $membraneIds)
            ->whereIn('remote_id', $availableMembraneIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);

        if ($existingMembraneIds->count() !== $membraneIds->count()) {
            throw ValidationException::withMessages([
                'membranes' => ['One or more selected membranes are not available.'],
            ]);
        }

        $result = DB::connection(config('database.default_predictions'))->transaction(
            function () use ($validated, $user, $priority, $membraneIds, $methodTypes, $smiles): array {
                $structures = $smiles->map(fn (string $smiles): PredictionStructure => PredictionStructure::query()
                    ->firstOrCreate(['canonical_smiles' => $smiles]));

                $datasets = [];
                $predictionsCreated = 0;

                foreach ($membraneIds as $membraneId) {
                    foreach ($methodTypes as $methodType) {
                        $dataset = PredictionDataset::query()->create([
                            'comment' => $validated['description'],
                            'token' => null,
                            'user_id' => $user->id,
                            'temperature' => (float) $validated['temperature'],
                            'membrane_id' => $membraneId,
                            'method_type' => $methodType,
                            'priority' => $priority,
                        ]);

                        foreach ($structures as $structure) {
                            $prediction = Prediction::query()->firstOrCreate(
                                [
                                    'structure_id' => $structure->id,
                                    'membrane_id' => $membraneId,
                                    'method_type' => $methodType,
                                    'temperature' => (float) $validated['temperature'],
                                ],
                                [
                                    'result_id' => null,
                                    'state' => Prediction::STATE_PREPARED,
                                    'step' => Prediction::STEP_PENDING,
                                    'priority' => $priority,
                                    'remote_method' => (new Prediction(['method_type' => $methodType]))->remotePredictionMethod(),
                                    'logs' => [],
                                ],
                            );

                            $prediction->forceFill([
                                'priority' => max((int) $prediction->priority, $priority),
                            ])->save();

                            $dataset->predictions()->syncWithoutDetaching([$prediction->id]);
                            $predictionsCreated++;
                        }

                        $datasets[] = $dataset->fresh(['predictionMembrane', 'user']);
                    }
                }

                return [
                    'datasets' => $datasets,
                    'predictions_count' => $predictionsCreated,
                ];
            }
        );

        return response()->json([
            'message' => 'Prediction calculations were queued.',
            'data' => [
                'datasets' => PredictionDatasetResource::collection(collect($result['datasets']))->resolve($request),
                'dataset_ids' => collect($result['datasets'])->pluck('id')->values(),
                'predictions_count' => $result['predictions_count'],
            ],
        ], 201);
    }

    public function index_datasets(Request $request)
    {
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = PredictionDataset::with(['user', 'predictionMembrane'])
            ->when(! $request->user()?->hasAdminRole(), fn ($query) => $query->where('user_id', $request->user()?->id))
            ->filter($request->all())
            ->paginateFilter($per_page);

        return PredictionDatasetResource::collection($pubs);
    }

    public function index(Request $request, PredictionDataset $record)
    {
        $this->authorizeDatasetAccess($request, $record);
        $record->loadMissing(['user', 'predictionMembrane']);

        return PredictionDatasetResource::make($record);
    }

    public function updateDataset(Request $request, PredictionDataset $record)
    {
        $this->authorizeDatasetAccess($request, $record);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $record->update($validated);
        $record->loadMissing(['user', 'predictionMembrane']);

        return PredictionDatasetResource::make($record);
    }

    public function records(Request $request, PredictionDataset $record)
    {
        $this->authorizeDatasetAccess($request, $record);
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        // Add dataset id to params
        $request->merge([
            'datasetId' => $record->id,
        ]);

        $records = Prediction::filter($request->all())
            ->paginateFilter($per_page);

        return PredictionResource::collection($records);
    }

    public function structures(Request $request, PredictionDataset $record)
    {
        $this->authorizeDatasetAccess($request, $record);
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        // Add dataset id to params
        $request->merge([
            'datasetId' => $record->id,
        ]);

        $records = PredictionStructure::filter($request->all())
            ->paginateFilter($per_page);

        return PredictionStructureResource::collection($records);
    }

    public function predictionsByStructure(Request $request, PredictionStructure $record)
    {
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        // Add dataset id to params
        $request->merge([
            'structureId' => $record->id,
        ]);

        $records = Prediction::filter($request->all())
            ->when(! $request->user()?->hasAdminRole(), function ($query) use ($request) {
                $query->whereHas('predictionDatasets', fn ($query) => $query->where('user_id', $request->user()?->id));
            })
            ->paginateFilter($per_page);

        return PredictionResource::collectionWithParsedResults($records);
    }

    private function authorizeDatasetAccess(Request $request, PredictionDataset $record): void
    {
        if ($request->user()?->hasAdminRole() || $record->user_id === $request->user()?->id) {
            return;
        }

        abort(403);
    }

    private function priorityValue(mixed $priority): int
    {
        if (is_numeric($priority)) {
            return match ((int) $priority) {
                Prediction::PRIORITY_HIGH => Prediction::PRIORITY_HIGH,
                Prediction::PRIORITY_MEDIUM => Prediction::PRIORITY_MEDIUM,
                default => Prediction::PRIORITY_LOW,
            };
        }

        return match (strtolower((string) $priority)) {
            'high' => Prediction::PRIORITY_HIGH,
            'medium' => Prediction::PRIORITY_MEDIUM,
            default => Prediction::PRIORITY_LOW,
        };
    }

    private function ensurePredictionMembranesAvailable(): void
    {
        Membrane::query()
            ->select(['id', 'name', 'abbreviation'])
            ->whereIn('id', $this->availablePredictionMembraneRemoteIds())
            ->orderBy('id')
            ->get()
            ->each(function (Membrane $membrane): void {
                PredictionMembrane::query()->firstOrCreate(
                    ['remote_id' => $membrane->id],
                    [
                        'name' => $membrane->name,
                        'abbreviation' => $membrane->abbreviation,
                    ],
                );
            });
    }

    /**
     * @return Collection<int, int>
     */
    private function availablePredictionMembraneRemoteIds(): Collection
    {
        return Membrane::query()
            ->whereHas('files', fn ($query) => $query->where('type', File::TYPE_COSMO_MEMBRANE))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }
}
