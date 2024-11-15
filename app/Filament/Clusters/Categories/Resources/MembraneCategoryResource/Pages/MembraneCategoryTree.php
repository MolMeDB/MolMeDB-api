<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource;
use App\Models\Category;
use Filament\Pages\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use SolutionForest\FilamentTree\Actions;
use SolutionForest\FilamentTree\Concern;
use SolutionForest\FilamentTree\Resources\Pages\TreePage as BasePage;
use SolutionForest\FilamentTree\Support\Utils;

class MembraneCategoryTree extends BasePage
{
    protected static string $resource = MembraneCategoryResource::class;

    protected static int $maxDepth = 2;

    public function getTreeQuery() : Builder
    {
        return Category::query()->where('type', Category::TYPE_MEMBRANE);
    }

    protected function getActions(): array
    {
        return [
            // $this->getCreateAction(),
            \Filament\Actions\CreateAction::make(),
            // SAMPLE CODE, CAN DELETE
            //\Filament\Pages\Actions\Action::make('sampleAction'),
        ];
    }

    protected function hasDeleteAction(): bool
    {
        return true;
    }

    protected function hasEditAction(): bool
    {
        return true;
    }

    protected function hasViewAction(): bool
    {
        return true;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Category::TYPE_MEMBRANE;
        return $data;
    }

    // CUSTOMIZE ICON OF EACH RECORD, CAN DELETE
    // public function getTreeRecordIcon(?\Illuminate\Database\Eloquent\Model $record = null): ?string
    // {
    //     return null;
    // }
}