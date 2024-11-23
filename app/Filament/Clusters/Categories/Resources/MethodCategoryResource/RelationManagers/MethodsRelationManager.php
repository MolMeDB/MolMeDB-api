<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategoryResource\RelationManagers;

use App\Enums\PermissionEnums;
use App\Filament\Resources\MethodResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MethodsRelationManager extends RelationManager
{
    protected static string $relationship = 'methods';

    public function form(Form $form): Form
    {
        return MethodResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->headerActions([
                Tables\Actions\AssociateAction::make()
                    ->visible(fn ($record): bool => auth()->user()->hasPermissionTo(PermissionEnums::MEMBRANE_METHOD_EDIT))
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
