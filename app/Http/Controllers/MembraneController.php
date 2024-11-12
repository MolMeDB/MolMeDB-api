<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMembraneRequest;
use App\Http\Requests\UpdateMembraneRequest;
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
        //
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
