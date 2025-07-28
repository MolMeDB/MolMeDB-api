<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\Stats;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $count_data = Stats::getCountStats();

        return response()->json([
            'data' => [
                'total' => [
                    'interactions' => [
                        'passive' => $count_data->total_passive_interactions,
                        'active' => $count_data->total_active_interactions
                    ],
                    'structures' => $count_data->total_structures,
                    'membranes' => $count_data->total_membranes,
                    'methods' => $count_data->total_methods
                ],
                'plots' => [
                    'interactionsLine' => [
                        'data' => Stats::getInteractionSubstanceHistory()->toArray()
                    ],
                    'databasesBar' => [
                        'items' => Stats::getDatabasesBarData()->toArray()
                    ],
                    'proteinsBar' => [
                        'items' => Stats::getProteinBarData()->toArray() // Assuming similar structure for proteins
                    ]
                ]
            ]
        ], 200);
    }

    public function publications(Request $request)
    {
        return response()->json([
            'data' => [
                'total' => [
                    'publications' => Publication::count(),
                ],
                'minPublishedYear' => Publication::min('year'),
                'byYear' => [
                    'data' => Stats::getPublicationByYearStatsData()->toArray()
                ],
                'byJournal' => [
                    'items' => Stats::getPublicationByJournalStatsData()->toArray()
                ]
            ]
        ], 200);
    }
}
