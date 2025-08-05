<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMembraneRequest;
use App\Http\Requests\UpdateMembraneRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\MembraneResource;
use App\Models\Category;
use App\Models\Membrane;

class MembraneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Returns list of membrane categories (with membranes)
     */
    public function categories()
    {
        $models = Category::where('type', Category::TYPE_MEMBRANE)
            ->with('membranes')
            ->orderby('order', 'asc')
            ->get();

        return CategoryCollection::make($models);
    }

    public function stats(Membrane $membrane)
    {
        return response()->json([
            'data' => [
                'membrane' => MembraneResource::make($membrane),
                'total' => [
                    'interactions_passive' => $membrane->interactionsPassive()->count(),
                    'structures' => $membrane->interactionsPassive()->distinct('structure_id')->count(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMembraneRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Membrane $membrane)
    {
        return MembraneResource::make($membrane);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMembraneRequest $request, Membrane $membrane)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Membrane $membrane)
    {
        //
    }
}
