<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePredictionDatasetRequest;
use App\Http\Requests\UpdatePredictionDatasetRequest;
use App\Models\PredictionDataset;

class PredictionDatasetController extends Controller
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
    public function store(StorePredictionDatasetRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PredictionDataset $predictionDataset)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePredictionDatasetRequest $request, PredictionDataset $predictionDataset)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PredictionDataset $predictionDataset)
    {
        //
    }
}
