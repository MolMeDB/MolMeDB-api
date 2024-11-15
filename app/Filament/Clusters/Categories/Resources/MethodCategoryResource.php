<?php

namespace App\Filament\Clusters\Categories\Resources;

use App\Filament\Clusters\Categories;
use App\Filament\Clusters\Categories\Resources\MethodCategoryResource\Pages;
use App\Filament\Clusters\Categories\Resources\MethodCategoryResource\RelationManagers;
use App\Models\Category;
use App\Models\MethodCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MethodCategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationLabel = "Method categories";
    protected static ?string $navigationBadgeTooltip = 'Manage method categories';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Categories::class;

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
                ->default(Category::TYPE_METHOD),
            
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('order')
                //     ->numeric(),
                Tables\Columns\TextColumn::make('parent.title')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->sortable(),
                Tables\Columns\TextColumn::make('methods_count')
                    ->label('# Methods')
                    ->counts('methods')
                    ->badge()
                    ->alignCenter()
                    ->colors([
                        'danger' => fn ($state) => $state === 0, 
                        'success' => fn ($state) => $state > 0,  
                    ])
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMethodCategories::route('/'),
            'create' => Pages\CreateMethodCategory::route('/create'),
            'edit' => Pages\EditMethodCategory::route('/{record}/edit'),
            'categoryTree' => Pages\MethodCategoryTree::route('/manage'),
        ];
    }
}
