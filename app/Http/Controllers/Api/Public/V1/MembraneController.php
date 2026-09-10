<?php

namespace App\Http\Controllers\Api\Public\V1;

use App\Http\Controllers\Api\Public\V1\Concerns\DownloadsExportFile;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Public\V1\CategoryTreeCollection;
use App\Http\Resources\Api\Public\V1\MembraneResource;
use App\Models\Category;
use App\Models\File;
use App\Models\Membrane;
use Illuminate\Http\Request;

/**
 * Read-only, unauthenticated public API. Deliberately not sharing code with
 * App\Http\Controllers\MembraneController — internal changes there must
 * never silently change this public contract.
 */
class MembraneController extends Controller
{
    use DownloadsExportFile;

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $membranes = Membrane::filter($request->only(['query', 'category_id']))
            ->paginateFilter($perPage);

        return MembraneResource::collection($membranes);
    }

    public function show(Membrane $membrane)
    {
        $membrane->load('categories');

        return MembraneResource::make($membrane);
    }

    public function stats(Membrane $membrane)
    {
        return response()->json([
            'data' => [
                'membrane' => MembraneResource::make($membrane),
                'total' => [
                    'interactions_passive' => $membrane->interactionsPassive()->count(),
                    'structures' => $membrane->interactionsPassive()->distinct('structure_id')->count(),
                ],
            ],
        ]);
    }

    public function categories()
    {
        $categories = Category::where('type', Category::TYPE_MEMBRANE)
            ->with('membranes')
            ->orderBy('order', 'asc')
            ->get();

        return CategoryTreeCollection::forMembranes($categories);
    }

    public function interactions(Membrane $membrane)
    {
        return $this->downloadLatestExport($membrane, File::TYPE_EXPORT_INTERACTIONS_MEMBRANE);
    }
}
