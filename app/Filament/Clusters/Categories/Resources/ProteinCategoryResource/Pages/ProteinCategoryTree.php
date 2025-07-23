<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategoryResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Clusters\Categories\Resources\ProteinCategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use SolutionForest\FilamentTree\Actions;
use SolutionForest\FilamentTree\Actions\DeleteAction;
use SolutionForest\FilamentTree\Resources\Pages\TreePage as BasePage;

class ProteinCategoryTree extends BasePage
{
    protected static string $resource = ProteinCategoryResource::class;
    // protected static ?string $cluster = Categories::class;

    protected static int $maxDepth = 5;

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('title'),
            \Filament\Forms\Components\Hidden::make('type')
                ->default(Category::TYPE_METHOD),
        ];
    }

    /**
     * Set custom page title
     */
    public function getTitle(): string
    {
        return 'Manage protein categories';
    }

    /**
     * Set custom breadcrumb label
     */
    public function getBreadcrumb(): ?string
    {
        return 'Protein';
    }

    /**
     * Set record title in the tree
     */
    public function getTreeRecordTitle(?\Illuminate\Database\Eloquent\Model $record = null): string
    {
        if (! $record) {
            return '';
        }
        $title = $record->title;
        $parent = $record->parent?->title;
        $total_proteins = $record->proteins()->count();
        return ($parent ? "{$parent} >> " : '') . "{$title}" . ($parent || $total_proteins ? " (# Proteins: {$total_proteins})"  : '');
    }

    /**
     * Change default query to filter just membrane type
     */
    public function getTreeQuery() : Builder
    {
        return Category::query()->where('type', Category::TYPE_PROTEIN);
    }

    /**
     * Hide this page in the main navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * Add custom actions
     */
    protected function getActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    /**
     * Adjust actions for each record
     */
    protected function getTreeActions(): array
    {
        return [
            Actions\EditAction::make()
                ->tooltip('Change category name'),
            Actions\ViewAction::make()
                ->icon(IconEnums::PROTEIN->value)
                ->color('warning')
                ->tooltip('Manage assigned proteins')
                ->visible(fn (Category $record) => static::getResource()::canEdit($record))
                ->modal(false)
                ->url(function (Category $record) {
                    return static::getResource()::getUrl('edit', ['record' => $record]);
                }),
            Actions\DeleteAction::make()
                ->tooltip('Delete category')
                ->before(fn (DeleteAction $action, Category $record) => ProteinCategoryResource::checkIfDeletable($action, $record)),
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

    // CUSTOMIZE ICON OF EACH RECORD, CAN DELETE
    // public function getTreeRecordIcon(?\Illuminate\Database\Eloquent\Model $record = null): ?string
    // {
    //     return null;
    // }
}