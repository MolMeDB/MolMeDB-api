<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProteinRequest;
use App\Http\Requests\UpdateProteinRequest;
use App\Models\Protein;

class ProteinController extends Controller
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
    public function store(StoreProteinRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Protein $protein)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProteinRequest $request, Protein $protein)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Protein $protein)
    {
        //
    }
}
