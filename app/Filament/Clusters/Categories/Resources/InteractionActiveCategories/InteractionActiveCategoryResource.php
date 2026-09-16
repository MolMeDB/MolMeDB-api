<?php

namespace App\Filament\Clusters\Categories\Resources\InteractionActiveCategories;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\SharedRelationManagers\InteractionsActiveRelationManager;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\ListInteractionActiveCategories;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\CreateInteractionActiveCategory;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\EditInteractionActiveCategory;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\InteractionActiveCategoryTree;
use App\Enums\IconEnums;
use App\Filament\Clusters\Categories\CategoriesCluster;
use App\Models\Category;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;

class InteractionActiveCategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationLabel = "Active interactions";
    protected static string|Htmlable|null $navigationBadgeTooltip = 'Manage categories';
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::INTERACTIONS->value;
    protected static ?string $cluster = CategoriesCluster::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', Category::TYPE_ACTIVE_INTERACTION);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->where('type', Category::TYPE_ACTIVE_INTERACTION)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return self::getNavigationBadge() > 0 ? 'primary' : 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->hint('Maximum 255 characters.')
                    ->columnSpanFull()
                    ->maxLength(255),
                Hidden::make('type')
                    ->default(Category::TYPE_ACTIVE_INTERACTION),
                
            ]);
    }

    /**
     * Checks, if membrane category can be deleted
     */
    // public static function checkIfDeletable(
    //     DeleteAction | DeleteAction | \SolutionForest\FilamentTree\Actions\DeleteAction $action 
    //     , Category $record) : void
    // {
    //     if (!$record->isDeletable()) {
    //         Notification::make()
    //             ->danger()
    //             ->title('The record cannot be deleted!')
    //             ->body('The category probably has assigned some active interactions.')
    //             ->send();

    //             $action->cancel();
    //     }
    //     else if($record->hasChildren())
    //     {
    //         Notification::make()
    //             ->danger()
    //             ->title('The record cannot be deleted!')
    //             ->body('The category probably has assigned children.')
    //             ->send();

    //             $action->cancel();
    //     }
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('parent.title')
                //     ->badge()
                //     ->color('warning')
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('interactions_active_count')
                    ->label('# Interactions')
                    ->counts('interactionsActive')
                    ->badge()
                    ->alignCenter()
                    ->colors([
                        'danger' => fn ($state) => $state === 0, 
                        'success' => fn ($state) => $state > 0,  
                    ])
                    ->sortable(),
                // Tables\Columns\TextColumn::make('children_count')
                //     ->label('# Children')
                //     ->counts('children')
                //     ->badge()
                //     ->alignCenter()
                //     ->color('primary')
                //     ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InteractionsActiveRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractionActiveCategories::route('/'),
            'create' => CreateInteractionActiveCategory::route('/create'),
            'edit' => EditInteractionActiveCategory::route('/{record}/edit'),
            'categoryTree' => InteractionActiveCategoryTree::route('/manage'),
        ];
    }
}
