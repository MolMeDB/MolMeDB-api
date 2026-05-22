<?php

namespace App\Filament\Clusters\Access\Resources\Users;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Access\Resources\Users\RelationManagers\RolesRelationManager;
use App\Filament\Clusters\Access\Resources\Users\RelationManagers\LogsRelationManager;
use App\Filament\Clusters\Access\Resources\Users\Pages\ListUsers;
use App\Filament\Clusters\Access\Resources\Users\Pages\EditUser;
use App\Enums\IconEnums;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::USERS->value; 
    protected static ?string $cluster = SettingsCluster::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Access rules';

    public static function form(Schema $schema): Schema
    {
        $is_disabled = (fn (User $record) => $record->id !== Auth::user()->id);

        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->hint(fn (User $record) => $is_disabled($record) ? 'Only owners can manage their profiles.' : '')
                    ->hintColor('danger')
                    ->disabled($is_disabled)
                    ->dehydrateStateUsing(fn ($state) => ucwords($state))
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->email()
                    ->disabled()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->disabled(),
                TextInput::make('affiliation')
                    ->disabled($is_disabled)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('affiliation')
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);

    }

    public static function getRelations(): array
    {
        return [
            RolesRelationManager::class,
            LogsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
