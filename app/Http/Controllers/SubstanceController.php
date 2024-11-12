<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubstanceRequest;
use App\Http\Requests\UpdateSubstanceRequest;
use App\Models\Substance;

class SubstanceController extends Controller
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
    public function store(StoreSubstanceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Substance $substance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubstanceRequest $request, Substance $substance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Substance $substance)
    {
        //
    }
}
