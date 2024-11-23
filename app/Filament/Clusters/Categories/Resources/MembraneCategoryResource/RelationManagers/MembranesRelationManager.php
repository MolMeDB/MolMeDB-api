<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\RelationManagers;

use App\Enums\PermissionEnums;
use App\Filament\Resources\MembraneResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembranesRelationManager extends RelationManager
{
    protected static string $relationship = 'membranes';

    public function form(Form $form): Form
    {
        return MembraneResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('abbreviation')
            ->columns([
                Tables\Columns\TextColumn::make('abbreviation'),
                Tables\Columns\TextColumn::make('name'),
            ])
            ->headerActions([
                Tables\Actions\AssociateAction::make()
                    ->preloadRecordSelect()
                    ->visible(fn ($record): bool => auth()->user()->hasPermissionTo(PermissionEnums::MEMBRANE_METHOD_EDIT)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
