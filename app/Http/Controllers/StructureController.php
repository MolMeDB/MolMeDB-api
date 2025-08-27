<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStructureRequest;
use App\Http\Requests\UpdateStructureRequest;
use App\Http\Resources\StructureResource;
use App\Models\Structure;
use Illuminate\Support\Facades\Http;
use Modules\Rdkit\Rdkit;

class StructureController extends Controller
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
    public function store(StoreStructureRequest $request)
    {
        //
    }

    public function mol3D(string $identifier)
    {
        $structure = Structure::where('identifier',$identifier)->first();

        if(!$structure?->id)
        {
            return response()->json([
                'message' => 'Structure not found'
            ], 404);
        }

        if($structure->molfile_3d)
        {
            return response($structure->molfile_3d)
                ->header('Content-Type', 'chemical/x-mdl-sdfile');
        }

        $rdkit = new Rdkit();

        $molContent = $rdkit->get_3d_structure($structure->canonical_smiles);

        if(!$molContent)
        {
            return response()->json([
                'message' => 'Structure not found'
            ], 404);
        }

        $structure->molfile_3d = $molContent;
        $structure->save();

        return response($molContent)
            ->header('Content-Type', 'chemical/x-mdl-sdfile');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $identifier)
    {
        $structure = Structure::where('identifier',$identifier)->first();

        if(!$structure?->id)
        {
            return response()->json([
                'message' => 'Structure not found'
            ], 404);
        }

        return StructureResource::make($structure);
    }

    public function formSelectMembranes(string $identifier)
    {
        $structure = Structure::where('identifier', $identifier)
            ->with([
                'interactionsPassive.dataset.membrane.categories.parent'
            ])
            ->first();

        if(!$structure?->id)
        {
            return response()->json([
                'message' => 'Structure not found'
            ], 404);
        }

        $membranes = $structure->interactionsPassive
            ->pluck('dataset.membrane')
            ->filter()
            ->unique('id')
            ->values();
        
        $tree = [];

        foreach ($membranes as $membrane) {
            /** @var \App\Models\Category $subcategory */
            $subcategory = $membrane->categories->first(); 

            if (!$subcategory) {
                continue;
            }

            $mainCategory = $subcategory->parent;

            $mainId = $mainCategory?->id ?? null;
            $subId = $subcategory?->id ?? null;

            if ($mainId && !isset($tree[$mainId])) {
                $tree[$mainId] = [
                    'placeholder' => $mainCategory->title,
                    'items' => []
                ];
            }

            if ($subId && !isset($tree[$mainId]['items'][$subId])) {
                $tree[$mainId]['items'][$subId] = [
                    'type' => 'category',
                    'category' => $subcategory->title,
                    'children' => []
                ];
            }

            $tree[$mainId]['items'][$subId]['children'][] = [
                'type' => 'item',
                'value' => $membrane->id,
                'label' => $membrane->abbreviation,
                'totalInteractions' => $structure->interactionsPassive->where('dataset.membrane_id', $membrane->id)->count()
            ];
        }

        foreach ($tree as $mainId => $mainCategory) {
            $tree[$mainId]['items'] = array_values($tree[$mainId]['items']);
        }

        return response()->json(array_values($tree));
    }

    public function formSelectMethods(string $identifier)
    {
        $structure = Structure::where('identifier', $identifier)
            ->with([
                'interactionsPassive.dataset.method.categories.parent'
            ])
            ->first();

        if(!$structure?->id)
        {
            return response()->json([
                'message' => 'Structure not found'
            ], 404);
        }

        $membraneIds = request()->query('membraneIds');

        $methods = $structure->interactionsPassive
            ->filter(function ($interactions) use ($membraneIds) {
                return $membraneIds === null || in_array($interactions->dataset->membrane_id, $membraneIds);
            })
            ->pluck('dataset.method')
            ->filter()
            ->unique('id')
            ->values();

        $tree = [];

        foreach ($methods as $method) {
            /** @var \App\Models\Category $subcategory */
            $subcategory = $method->categories->first(); 

            if (!$subcategory) {
                continue;
            }

            $mainCategory = $subcategory->parent;

            $mainId = $mainCategory?->id ?? null;
            $subId = $subcategory?->id ?? null;

            if ($mainId && !isset($tree[$mainId])) {
                $tree[$mainId] = [
                    'placeholder' => $mainCategory->title,
                    'items' => []
                ];
            }

            if ($subId && !isset($tree[$mainId]['items'][$subId])) {
                $tree[$mainId]['items'][$subId] = [
                    'type' => 'category',
                    'category' => $subcategory->title,
                    'children' => []
                ];
            }

            $tree[$mainId]['items'][$subId]['children'][] = [
                'type' => 'item',
                'value' => $method->id,
                'label' => $method->abbreviation,
                'totalInteractions' => $structure->interactionsPassive->where('dataset.method_id', $method->id)->count()
            ];
        }

        foreach ($tree as $mainId => $mainCategory) {
            $tree[$mainId]['items'] = array_values($tree[$mainId]['items']);
        }

        return response()->json(array_values($tree));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStructureRequest $request, Structure $structure)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Structure $structure)
    {
        //
    }
}
