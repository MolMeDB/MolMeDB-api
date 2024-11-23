<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategoryResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Clusters\Categories;
use App\Filament\Clusters\Categories\Resources\MethodCategoryResource;
use App\Models\Category;
use Filament\Pages\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentTree\Actions;
use SolutionForest\FilamentTree\Actions\DeleteAction;
use SolutionForest\FilamentTree\Concern;
use SolutionForest\FilamentTree\Resources\Pages\TreePage as BasePage;
use SolutionForest\FilamentTree\Support\Utils;

class MethodCategoryTree extends BasePage
{
    protected static string $resource = MethodCategoryResource::class;
    // protected static ?string $cluster = Categories::class;

    protected static int $maxDepth = 2;

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('title'),
            \Filament\Forms\Components\Hidden::make('type')
                ->default(Category::TYPE_MEMBRANE),
        ];
    }

    /**
     * Set custom page title
     */
    public function getTitle(): string
    {
        return 'Manage method categories';
    }

    /**
     * Set custom breadcrumb label
     */
    public function getBreadcrumb(): ?string
    {
        return 'Method';
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
        $total_methods = $record->methods()->count();
        return ($parent ? "{$parent} >> " : '') . "{$title} (# Methods: {$total_methods})";
    }

    /**
     * Change default query to filter just membrane type
     */
    public function getTreeQuery() : Builder
    {
        return Category::query()->where('type', Category::TYPE_METHOD);
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
                ->icon(IconEnums::METHOD->value)
                ->color('warning')
                ->tooltip('Manage assigned methods')
                ->modal(false)
                ->url(function (Category $record) {
                    return static::getResource()::getUrl('edit_record', ['record' => $record]);
                }),
            Actions\DeleteAction::make()
                ->tooltip('Delete category')
                ->before(fn (DeleteAction $action, Category $record) => MethodCategoryResource::checkIfDeletable($action, $record)),
        ];
    }

    /**
     * Set type before adding new record
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Category::TYPE_METHOD;
        return $data;
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