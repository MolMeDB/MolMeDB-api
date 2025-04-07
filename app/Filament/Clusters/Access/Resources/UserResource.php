<?php

namespace App\Filament\Clusters\Access\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Access\Resources\UserResource\Pages;
use App\Filament\Clusters\Access\Resources\UserResource\RelationManagers;
use App\Filament\Clusters\Settings;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = IconEnums::USERS->value; 
    protected static ?string $cluster = Settings::class;
    protected static ?string $navigationGroup = 'Access rules';

    public static function form(Form $form): Form
    {
        $is_disabled = (fn (User $record) => $record->id !== Auth::user()->id);

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->hint(fn (User $record) => $is_disabled($record) ? 'Only owners can manage their profiles.' : '')
                    ->hintColor('danger')
                    ->disabled($is_disabled)
                    ->dehydrateStateUsing(fn ($state) => ucwords($state))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->disabled()
                    ->required(),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->disabled(),
                Forms\Components\TextInput::make('affiliation')
                    ->disabled($is_disabled)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('affiliation')
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable(),
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
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);

    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
            RelationManagers\LogsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
