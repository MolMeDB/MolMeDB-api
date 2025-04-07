<?php

namespace App\Filament\Clusters\Access\Resources\UserResource\RelationManagers;

use App\Enums\IconEnums;
use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Access\Resources\UserResource;
use App\Policies\RolePolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';
    protected static ?string $icon = IconEnums::ROLES->value;
    protected static ?string $title = 'Access roles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        if(Auth::user()->hasRole(RoleEnums::ADMIN))
                            return $query;
                        else // If not admin, cannot assign admin role to anyone
                            return $query->where('name', '!=', RoleEnums::ADMIN->value);
                    })
                    ->color('primary')
                    ->visible(fn ($record): bool => RolePolicy::attach(Auth::user()))
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->visible(fn ($record): bool => $this->ownerRecord->id !== Auth::user()->id && // Cannot detach own roles.
                        RolePolicy::attach(Auth::user())) 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
