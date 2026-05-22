<?php

namespace App\Filament\Resources\DocumentArticles\Pages;

use App\Filament\Resources\DocumentArticles\DocumentArticleResource;
use App\Models\DocumentArticle;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDocumentArticle extends CreateRecord
{
    protected static string $resource = DocumentArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if (! $parentId) {
            return $data;
        }

        $parent = DocumentArticle::query()->find($parentId);
        if ($parent?->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Only two levels of articles are supported.',
            ]);
        }

        return $data;
    }
}
