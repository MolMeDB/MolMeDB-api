<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\UpdatePublicationRequest;
use App\Http\Resources\PublicationResource;
use App\Models\Publication;
use Illuminate\Http\Request;

class PublicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = 10; // Default value
        if($request->query('per_page') && is_numeric($request->query('per_page')))
        {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = Publication::filter($request->all())
            ->paginateFilter($per_page);

        return PublicationResource::collection($pubs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePublicationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Publication $publication)
    {
        return new PublicationResource($publication);
    }

    /**
     * Display the specified resource with stats
     */
    public function stats(Publication $publication)
    {
        $publication->load('files');

        $publication->loadCount([
            'interactionsPassive', 
            'interactionsActive',
            'membranes',
            'methods',
            'datasets',
            'authors',
        ]);

        return new PublicationResource($publication);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePublicationRequest $request, Publication $publication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Publication $publication)
    {
        //
    }
}
