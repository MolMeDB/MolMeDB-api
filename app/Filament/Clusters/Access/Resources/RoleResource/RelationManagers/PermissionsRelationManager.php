<?php

namespace App\Filament\Clusters\Access\Resources\RoleResource\RelationManagers;

use App\Filament\Clusters\Access\Resources\PermissionResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Key')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->sortable()
                    ->wrap()
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->multiple()
                    ->color('primary')
                    ->preloadRecordSelect()
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->visible(fn ($record): bool => $this->ownerRecord->id !== 1), // Cannot detach admin permissions
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
