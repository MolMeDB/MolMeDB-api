<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategories;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Categories\Resources\MethodCategories\RelationManagers\MethodsRelationManager;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\ListMethodCategories;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\CreateMethodCategory;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\EditMethodCategory;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\MethodCategoryTree;
use App\Enums\IconEnums;
use App\Filament\Clusters\Categories\CategoriesCluster;
use App\Filament\Clusters\Categories\Resources\MethodCategoryResource\Pages;
use App\Filament\Clusters\Categories\Resources\MethodCategoryResource\RelationManagers;
use App\Models\Category;
use App\Models\Method;
use App\Models\MethodCategory;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MethodCategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationLabel = "Method";
    protected static string|Htmlable|null $navigationBadgeTooltip = 'Manage method categories';

    protected static string | \BackedEnum | null $navigationIcon = IconEnums::METHOD->value;

    protected static ?string $cluster = CategoriesCluster::class;

  
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', Category::TYPE_METHOD);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->where('type', Category::TYPE_METHOD)->count();
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
                    ->default(Category::TYPE_METHOD),
            
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
    //             ->body('The category probably has assigned some methods.')
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
                TextColumn::make('parent.title')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('methods_count')
                    ->label('# Methods')
                    ->counts('methods')
                    ->badge()
                    ->alignCenter()
                    ->colors([
                        'danger' => fn ($state) => $state === 0, 
                        'success' => fn ($state) => $state > 0,  
                    ])
                    ->sortable(),
                TextColumn::make('children_count')
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
            MethodsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMethodCategories::route('/'),
            'create' => CreateMethodCategory::route('/create'),
            'edit_record' => EditMethodCategory::route('/{record}/edit'),
            'categoryTree' => MethodCategoryTree::route('/manage'),
        ];
    }
}
