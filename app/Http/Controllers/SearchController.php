<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchStructureRequest;
use App\Http\Resources\Search\SearchMembraneResource;
use App\Http\Resources\Search\SearchMethodResource;
use App\Http\Resources\Search\SearchProteinResource;
use App\Http\Resources\Search\SearchPublicationResource;
use App\Http\Resources\Search\SearchStructureResource;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function structure(SearchStructureRequest $request)
    {
        $filters = $request->filters();
        $query = Structure::filter($filters);

        $pubs = array_key_exists('substructure', $filters)
            ? $query->simplePaginateFilter($request->perPage())
            : $query->paginateFilter($request->perPage());

        return SearchStructureResource::collection($pubs);
    }

    public function membrane(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $pubs = Membrane::filter($request->all())
            ->paginateFilter($perPage);

        return SearchMembraneResource::collection($pubs);
    }

    public function method(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $pubs = Method::filter($request->all())
            ->paginateFilter($perPage);

        return SearchMethodResource::collection($pubs);
    }

    public function protein(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $pubs = Protein::filter($request->all())
            ->paginateFilter($perPage);

        return SearchProteinResource::collection($pubs);
    }

    public function dataset(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $pubs = Publication::filter($request->all())
            ->paginateFilter($perPage);

        return SearchPublicationResource::collection($pubs);
    }
}
