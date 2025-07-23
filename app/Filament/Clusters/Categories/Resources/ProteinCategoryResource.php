<?php

namespace App\Filament\Clusters\Categories\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Categories;
use App\Filament\Clusters\Categories\Resources\ProteinCategoryResource\Pages;
use App\Filament\Clusters\Categories\Resources\ProteinCategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProteinCategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationLabel = "Protein";
    protected static ?string $navigationBadgeTooltip = "Manage protein categories";

    protected static ?string $navigationIcon = IconEnums::PROTEIN->value;

    protected static ?string $cluster = Categories::class;

     public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', Category::TYPE_PROTEIN);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->where('type', Category::TYPE_PROTEIN)->count();
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
                    ->default(Category::TYPE_PROTEIN),
            
        ]);
    }

    /**
     * Checks, if protein category can be deleted
     */
    public static function checkIfDeletable(
        Tables\Actions\DeleteAction | \Filament\Actions\DeleteAction | \SolutionForest\FilamentTree\Actions\DeleteAction $action 
        , Category $record) : void
    {
        if (!$record->isDeletable()) {
            Notification::make()
                ->danger()
                ->title('The record cannot be deleted!')
                ->body('The category probably has assigned some proteins.')
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
                Tables\Columns\TextColumn::make('parent.title')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('proteins_count')
                    ->label('# Proteins')
                    ->counts('proteins')
                    ->badge()
                    ->alignCenter()
                    ->colors([
                        'danger' => fn ($state) => $state === 0, 
                        'success' => fn ($state) => $state > 0,  
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('children_count')
                    ->label('# Children')
                    ->counts('children')
                    ->badge()
                    ->alignCenter()
                    ->color('primary')
                    ->sortable(),
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
            RelationManagers\ProteinsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProteinCategories::route('/'),
            'create' => Pages\CreateProteinCategory::route('/create'),
            'edit' => Pages\EditProteinCategory::route('/{record}/edit'),
            'categoryTree' => Pages\ProteinCategoryTree::route('/manage'),
        ];
    }
}
