<?php

namespace App\Filament\Resources\FeedbackSubmissionResource\Pages;

use App\Filament\Resources\FeedbackSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListFeedbackSubmissions extends ListRecords
{
    protected static string $resource = FeedbackSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
