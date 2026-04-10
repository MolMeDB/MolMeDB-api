<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategories\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AttachAction;
use Filament\Forms\Components\Hidden;
use Filament\Actions\EditAction;
use App\Enums\PermissionEnums;
use App\Filament\Resources\Methods\MethodResource;
use App\Models\Method;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MethodsRelationManager extends RelationManager
{
    protected static string $relationship = 'methods';

    public function form(Schema $schema): Schema
    {
        return MethodResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('abbreviation')
            ->columns([
                TextColumn::make('abbreviation')
                    ->sortable(),
                TextColumn::make('name')
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->visible(fn ($record): bool => Auth::user()->hasPermissionTo(PermissionEnums::MEMBRANE_METHOD_EDIT))
                    ->recordSelectSearchColumns(['name', 'abbreviation'])
                    ->multiple()
                    ->recordSelect(fn (Select $select) => 
                        $select->placeholder('Please, select method')
                            ->searchable())
                    ->schema(fn (AttachAction $action) => [
                        $action->getRecordSelect(),
                        Hidden::make('model_type')
                            ->default(Method::class),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
