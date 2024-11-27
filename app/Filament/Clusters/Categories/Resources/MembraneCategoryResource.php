<?php

namespace App\Filament\Clusters\Categories\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Categories;
use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\Pages;
use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\Pages\MembraneCategoryTree;
use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\RelationManagers;
use App\Models\Category;
use App\Models\MembraneCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembraneCategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationLabel = "Membrane";
    protected static ?string $navigationBadgeTooltip = 'Manage membrane categories';

    protected static ?string $navigationIcon = IconEnums::MEMBRANE->value;

    protected static ?string $cluster = Categories::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', Category::TYPE_MEMBRANE);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->where('type', Category::TYPE_MEMBRANE)->count();
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
                    ->default(Category::TYPE_MEMBRANE),
                
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
                ->body('The category probably has assigned some membranes.')
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
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('membranes_count')
                    ->label('# Membranes')
                    ->counts('membranes')
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
                // Tables\Actions\ViewAction::make()
                //     ->modalWidth('7xl')
                //     ->modalContent(function (Category $record) {
                //        return view('filament.clusters.category.pages.membrane', [
                //            'record' => $record,
                //            'relationManagers' => self::getRelations()
                //        ]);
                //     }),
                // Tables\Actions\DeleteAction::make()
                //     ->before(fn (Tables\Actions\DeleteAction $action, Category $record) => self::checkIfDeletable($action, $record))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MembranesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembraneCategories::route('/'),
            'create' => Pages\CreateMembraneCategory::route('/create'),
            'edit_record' => Pages\EditMembraneCategory::route('/{record}/edit'),
            'categoryTree' => Pages\MembraneCategoryTree::route('/manage'),
        ];
    }
}
