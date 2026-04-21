<?php

namespace App\Filament\Resources\DocumentArticles\Pages;

use App\Filament\Resources\DocumentArticles\DocumentArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentArticles extends ListRecords
{
    protected static string $resource = DocumentArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
