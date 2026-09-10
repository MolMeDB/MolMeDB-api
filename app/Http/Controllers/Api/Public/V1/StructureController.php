<?php

namespace App\Http\Controllers\Api\Public\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Public\V1\SearchStructureRequest;
use App\Http\Resources\Api\Public\V1\InteractionActiveResource;
use App\Http\Resources\Api\Public\V1\InteractionPassiveResource;
use App\Http\Resources\Api\Public\V1\StructureResource;
use App\Models\Structure;
use Illuminate\Http\Request;

/**
 * Read-only, unauthenticated public API. Deliberately not sharing code with
 * App\Http\Controllers\StructureController — internal changes there must
 * never silently change this public contract.
 */
class StructureController extends Controller
{
    public function index(SearchStructureRequest $request)
    {
        $filters = $request->filters();

        // Structures without an identifier yet (pending curation) can't be
        // fetched by any other public endpoint (all keyed by identifier) —
        // excluding them avoids listing dead-end records.
        $query = Structure::filter($filters)->whereNotNull('identifier');

        $structures = filled($filters['substructure'] ?? null)
            ? $query->simplePaginateFilter($request->perPage())
            : $query->paginateFilter($request->perPage());

        return StructureResource::collection($structures);
    }

    public function show(string $identifier)
    {
        $structure = $this->findStructure($identifier);
        $structure->load('identifiers');

        return StructureResource::make($structure)->withDetails();
    }

    public function stats(string $identifier)
    {
        $structure = $this->findStructure($identifier);

        return response()->json([
            'data' => [
                'structure' => StructureResource::make($structure),
                'total' => [
                    'interactions_passive' => $structure->interactionsPassive()->count(),
                    'interactions_active' => $structure->interactionsActive()->count(),
                ],
            ],
        ]);
    }

    public function interactionsPassive(string $identifier, Request $request)
    {
        $structure = $this->findStructure($identifier);

        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $interactions = $structure->interactionsPassive()
            ->with(['dataset.membrane', 'dataset.method', 'dataset.publications', 'publication'])
            ->paginate($perPage);

        return InteractionPassiveResource::collection($interactions);
    }

    public function interactionsActive(string $identifier, Request $request)
    {
        $structure = $this->findStructure($identifier);

        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $interactions = $structure->interactionsActive()
            ->with(['protein', 'dataset.publications', 'publication'])
            ->paginate($perPage);

        return InteractionActiveResource::collection($interactions);
    }

    private function findStructure(string $identifier): Structure
    {
        $structure = Structure::where('identifier', $identifier)->first();

        abort_unless($structure?->id, 404, 'Structure not found.');

        return $structure;
    }
}
