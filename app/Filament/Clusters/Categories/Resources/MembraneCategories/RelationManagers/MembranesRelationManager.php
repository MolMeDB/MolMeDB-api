<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategories\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AssociateAction;
use Filament\Actions\EditAction;
use App\Enums\PermissionEnums;
use App\Filament\Resources\Membranes\MembraneResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembranesRelationManager extends RelationManager
{
    protected static string $relationship = 'membranes';

    public function form(Schema $schema): Schema
    {
        return MembraneResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('abbreviation')
            ->columns([
                TextColumn::make('abbreviation'),
                TextColumn::make('name'),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->preloadRecordSelect()
                    ->visible(fn ($record): bool => auth()->user()->hasPermissionTo(PermissionEnums::MEMBRANE_METHOD_EDIT)),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
