<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInteractionActiveRequest;
use App\Http\Requests\UpdateInteractionActiveRequest;
use App\Http\Resources\InteractionActiveResource;
use App\Models\InteractionActive;
use App\Models\Structure;
use Illuminate\Http\Request;

class InteractionActiveController extends Controller
{
    public function byStructure(string $identifier, Request $request)
    {
        $structure = Structure::where('identifier', $identifier)
            ->with([
                'interactionsActive.dataset'
            ])
            ->first();

        if(!$structure?->id)
        {
            return response()->json([
                'message' => 'Structure not found'
            ], 404);
        }

        $per_page = 10; // Default value
        if($request->query('per_page') && is_numeric($request->query('per_page')))
        {
            $per_page = intval($request->query('per_page'));
        }

        $params = $request->all();
        $params['structureId'] = $structure->id;

        $interactions = InteractionActive::filter($params)
            ->paginateFilter($per_page);

        return InteractionActiveResource::collection($interactions);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInteractionActiveRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InteractionActive $interactionActive)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInteractionActiveRequest $request, InteractionActive $interactionActive)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InteractionActive $interactionActive)
    {
        //
    }
}
