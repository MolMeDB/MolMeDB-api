<?php

namespace App\Filament\Clusters\Access\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Access\Resources\PermissionResource\Pages;
use App\Filament\Clusters\Access\Resources\PermissionResource\RelationManagers;
use App\Filament\Clusters\Settings;
use App\Models\Permission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static ?string $navigationIcon = IconEnums::PERMISSIONS->value;
    protected static ?string $cluster = Settings::class;
    protected static ?string $navigationGroup = 'Access rules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
            RelationManagers\RolesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            // 'create' => Pages\CreatePermission::route('/create'),
            // 'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
