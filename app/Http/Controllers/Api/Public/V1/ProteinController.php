<?php

namespace App\Http\Controllers\Api\Public\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Public\V1\CategoryTreeCollection;
use App\Http\Resources\Api\Public\V1\InteractionActiveResource;
use App\Http\Resources\Api\Public\V1\ProteinResource;
use App\Models\Category;
use App\Models\Protein;
use Illuminate\Http\Request;

/**
 * Read-only, unauthenticated public API. Deliberately not sharing code with
 * App\Http\Controllers\ProteinController — internal changes there must
 * never silently change this public contract.
 */
class ProteinController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $proteins = Protein::filter($request->only(['query', 'category_id']))
            ->with('identifiers')
            ->paginateFilter($perPage);

        return ProteinResource::collection($proteins);
    }

    public function show(Protein $protein)
    {
        $protein->load('identifiers');

        return ProteinResource::make($protein);
    }

    public function stats(Protein $protein)
    {
        return response()->json([
            'data' => [
                'protein' => ProteinResource::make($protein),
                'total' => [
                    'interactions_active' => $protein->interactionsActive()->count(),
                    'structures' => $protein->structures()->count(),
                ],
            ],
        ]);
    }

    public function categories()
    {
        $categories = Category::where('type', Category::TYPE_PROTEIN)
            ->with('proteins')
            ->orderBy('order', 'asc')
            ->get();

        return CategoryTreeCollection::forProteins($categories);
    }

    public function interactions(Protein $protein, Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $interactions = $protein->interactionsActive()
            ->with(['protein', 'dataset.publications', 'publication'])
            ->paginate($perPage);

        return InteractionActiveResource::collection($interactions);
    }
}
