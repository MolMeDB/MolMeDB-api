<?php

namespace App\Http\Controllers\Api\Public\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Public\V1\Concerns\DownloadsExportFile;
use App\Http\Resources\Api\Public\V1\PublicationResource;
use App\Models\File;
use App\Models\Publication;
use Illuminate\Http\Request;

/**
 * Read-only, unauthenticated public API. Deliberately not sharing code with
 * App\Http\Controllers\PublicationController — internal changes there must
 * never silently change this public contract.
 */
class PublicationController extends Controller
{
    use DownloadsExportFile;

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $publications = Publication::filter($request->only(['query']))
            ->paginateFilter($perPage);

        return PublicationResource::collection($publications);
    }

    public function show(Publication $publication)
    {
        $publication->load('authors');

        return PublicationResource::make($publication)->withDetails();
    }

    public function stats(Publication $publication)
    {
        $publication->loadCount([
            'interactionsPassive',
            'interactionsActive',
            'membranes',
            'methods',
            'datasets',
        ]);

        return response()->json([
            'data' => [
                'publication' => PublicationResource::make($publication),
                'total' => [
                    'interactions_passive' => $publication->interactions_passive_count,
                    'interactions_active' => $publication->interactions_active_count,
                    'membranes' => $publication->membranes_count,
                    'methods' => $publication->methods_count,
                    'datasets' => $publication->datasets_count,
                ],
            ],
        ]);
    }

    public function interactionsPassive(Publication $publication)
    {
        return $this->downloadLatestExport($publication, File::TYPE_EXPORT_INTERACTIONS_PASSIVE_PUBLICATION);
    }

    public function interactionsActive(Publication $publication)
    {
        return $this->downloadLatestExport($publication, File::TYPE_EXPORT_INTERACTIONS_ACTIVE_PUBLICATION);
    }
}
