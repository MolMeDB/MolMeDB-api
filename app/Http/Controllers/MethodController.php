<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMethodRequest;
use App\Http\Requests\UpdateMethodRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\MethodResource;
use App\Models\Category;
use App\Models\Method;

class MethodController extends Controller
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
    public function store(StoreMethodRequest $request)
    {
        //
    }

    /**
     * Returns list of membrane categories (with membranes)
     */
    public function categories()
    {
        $models = Category::where('type', Category::TYPE_METHOD)
            ->with('methods')
            ->orderby('order', 'asc')
            ->get();

        return CategoryCollection::make($models);
    }

    /**
     * Display the specified resource.
     */
    public function show(Method $method)
    {
        return MethodResource::make($method);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMethodRequest $request, Method $method)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Method $method)
    {
        //
    }
}
