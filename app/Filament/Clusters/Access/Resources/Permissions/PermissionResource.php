<?php

namespace App\Filament\Clusters\Access\Resources\Permissions;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Access\Resources\Permissions\RelationManagers\RolesRelationManager;
use App\Filament\Clusters\Access\Resources\Permissions\Pages\ListPermissions;
use App\Enums\IconEnums;
use App\Filament\Clusters\Access\Resources\PermissionResource\Pages;
use App\Filament\Clusters\Access\Resources\PermissionResource\RelationManagers;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Permission;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::PERMISSIONS->value;
    protected static ?string $cluster = SettingsCluster::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Access rules';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
            RolesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            // 'create' => Pages\CreatePermission::route('/create'),
            // 'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
