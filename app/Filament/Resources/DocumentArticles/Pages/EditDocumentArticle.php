<?php

namespace App\Filament\Resources\DocumentArticles\Pages;

use App\Filament\Resources\DocumentArticles\DocumentArticleResource;
use App\Models\DocumentArticle;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditDocumentArticle extends EditRecord
{
    protected static string $resource = DocumentArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if (! $parentId) {
            return $data;
        }

        $parent = DocumentArticle::query()->find($parentId);
        if (! $parent || $parent->id === $this->record->id || $parent->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Only two levels of articles are supported.',
            ]);
        }

        return $data;
    }
}
