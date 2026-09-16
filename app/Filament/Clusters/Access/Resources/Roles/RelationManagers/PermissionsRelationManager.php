<?php

namespace App\Filament\Clusters\Access\Resources\Roles\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Access\Resources\Permissions\PermissionResource;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label('Key')
                    ->sortable(),
                TextColumn::make('description')
                    ->sortable()
                    ->wrap()
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->multiple()
                    ->color('primary')
                    ->preloadRecordSelect()
            ])
            ->recordActions([
                DetachAction::make()
                    ->visible(fn ($record): bool => $this->ownerRecord->id !== 1), // Cannot detach admin permissions
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
