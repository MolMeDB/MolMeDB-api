<?php

namespace App\Http\Controllers;

use App\Http\Resources\PredictionDatasetResource;
use App\Http\Resources\PredictionResource;
use App\Http\Resources\PredictionStructureResource;
use Illuminate\Http\Request;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionDataset;
use Modules\PredictionWorkers\Models\PredictionStructure;

class PredictionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index_datasets(Request $request)
    {
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        $pubs = PredictionDataset::with('user')
            ->filter($request->all())
            ->paginateFilter($per_page);

        return PredictionDatasetResource::collection($pubs);
    }

    public function index(PredictionDataset $record)
    {
        $record->loadMissing('user');

        return PredictionDatasetResource::make($record);
    }

    public function records(Request $request, PredictionDataset $record)
    {
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        // Add dataset id to params
        $request->merge([
            'datasetId' => $record->id,
        ]);

        $records = Prediction::filter($request->all())
            ->paginateFilter($per_page);

        return PredictionResource::collection($records);
    }

    public function structures(Request $request, PredictionDataset $record)
    {
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        // Add dataset id to params
        $request->merge([
            'datasetId' => $record->id,
        ]);

        $records = PredictionStructure::filter($request->all())
            ->paginateFilter($per_page);

        return PredictionStructureResource::collection($records);
    }

    public function predictionsByStructure(Request $request, PredictionStructure $record)
    {
        $per_page = 10; // Default value
        if ($request->query('per_page') && is_numeric($request->query('per_page'))) {
            $per_page = intval($request->query('per_page'));
        }

        // Add dataset id to params
        $request->merge([
            'structureId' => $record->id,
        ]);

        $records = Prediction::filter($request->all())
            ->paginateFilter($per_page);

        return PredictionResource::collectionWithParsedResults($records);
    }
}
