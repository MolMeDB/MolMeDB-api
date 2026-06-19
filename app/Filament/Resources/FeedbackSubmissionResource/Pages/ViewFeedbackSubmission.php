<?php

namespace App\Filament\Resources\FeedbackSubmissionResource\Pages;

use App\Filament\Resources\FeedbackSubmissionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedbackSubmission extends ViewRecord
{
    protected static string $resource = FeedbackSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
