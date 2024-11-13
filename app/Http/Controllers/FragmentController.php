<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFragmentRequest;
use App\Http\Requests\UpdateFragmentRequest;
use App\Models\Fragment;

class FragmentController extends Controller
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
    public function store(StoreFragmentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Fragment $fragment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFragmentRequest $request, Fragment $fragment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fragment $fragment)
    {
        //
    }
}
