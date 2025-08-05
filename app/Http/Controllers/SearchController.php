<?php

namespace App\Http\Controllers;

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
    public function structure(Request $request) {
        $per_page = 10; // Default value
        if($request->query('per_page') && is_numeric($request->query('per_page')))
        {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = Structure::filter($request->all())
            ->paginateFilter($per_page);

        return SearchStructureResource::collection($pubs);
    }

    public function membrane(Request $request) {
        $per_page = 10; // Default value
        if($request->query('per_page') && is_numeric($request->query('per_page')))
        {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = Membrane::filter($request->all())
            ->paginateFilter($per_page);

        return SearchMembraneResource::collection($pubs);
    }

    public function method(Request $request) {
        $per_page = 10; // Default value
        if($request->query('per_page') && is_numeric($request->query('per_page')))
        {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = Method::filter($request->all())
            ->paginateFilter($per_page);

        return SearchMethodResource::collection($pubs);
    }

    public function protein(Request $request) {
        $per_page = 10; // Default value
        if($request->query('per_page') && is_numeric($request->query('per_page')))
        {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = Protein::filter($request->all())
            ->paginateFilter($per_page);

        return SearchProteinResource::collection($pubs);
    }

    public function dataset(Request $request) {
        $per_page = 10; // Default value
        if($request->query('per_page') && is_numeric($request->query('per_page')))
        {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = Publication::filter($request->all())
            ->paginateFilter($per_page);

        return SearchPublicationResource::collection($pubs);
    }
}
