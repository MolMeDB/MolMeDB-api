<?php

namespace App\Filament\Clusters\Access\Resources;

use App\Enums\IconEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Access\Resources\RoleResource\Pages;
use App\Filament\Clusters\Access\Resources\RoleResource\RelationManagers;
use App\Filament\Clusters\Settings;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = IconEnums::ROLES->value;
    protected static ?string $cluster = Settings::class;
    protected static ?string $navigationGroup = 'Access rules';

    public static function form(Form $form): Form
    {
        $default_enums = array_map(fn ($enum) => $enum->value, RoleEnums::cases());

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->disabled(fn ($record) => $record && in_array($record->name, $default_enums))
                    ->hint(fn($record) => $record && in_array($record->name, $default_enums) ? 'Default roles cannot be changed.' : '')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->badge()
                    ->label('# Users')
                    ->alignCenter()
                    ->color('warning')
                    ->numeric(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->badge()
                    ->tooltip(fn ($record) => $record->permissions->pluck('description')->implode(" - "))
                    ->label('# Permissions')
                    ->alignCenter()
                    ->color('primary')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            RelationManagers\PermissionsRelationManager::class,
            RelationManagers\UsersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
