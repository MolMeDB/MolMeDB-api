<?php

namespace App\Filament\Clusters\Categories\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Categories;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategoryResource\Pages;
use App\Filament\Resources\SharedRelationManagers;
use App\Models\Category;
use App\Models\InteractionActiveCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Notification;

class InteractionActiveCategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationLabel = "Active interactions";
    protected static ?string $navigationBadgeTooltip = 'Manage categories';
    protected static ?string $navigationIcon = IconEnums::INTERACTIONS->value;
    protected static ?string $cluster = Categories::class;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->hint('Maximum 255 characters.')
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Hidden::make('type')
                    ->default(Category::TYPE_ACTIVE_INTERACTION),
                
            ]);
    }

    /**
     * Checks, if membrane category can be deleted
     */
    public static function checkIfDeletable(
        Tables\Actions\DeleteAction | \Filament\Actions\DeleteAction | \SolutionForest\FilamentTree\Actions\DeleteAction $action 
        , Category $record) : void
    {
        if (!$record->isDeletable()) {
            Notification::make()
                ->danger()
                ->title('The record cannot be deleted!')
                ->body('The category probably has assigned some active interactions.')
                ->send();

                $action->cancel();
        }
        else if($record->hasChildren())
        {
            Notification::make()
                ->danger()
                ->title('The record cannot be deleted!')
                ->body('The category probably has assigned children.')
                ->send();

                $action->cancel();
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('parent.title')
                //     ->badge()
                //     ->color('warning')
                //     ->sortable()
                //     ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('interactions_active_count')
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
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SharedRelationManagers\InteractionsActiveRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractionActiveCategories::route('/'),
            'create' => Pages\CreateInteractionActiveCategory::route('/create'),
            'edit' => Pages\EditInteractionActiveCategory::route('/{record}/edit'),
            'categoryTree' => Pages\InteractionActiveCategoryTree::route('/manage'),
        ];
    }
}
