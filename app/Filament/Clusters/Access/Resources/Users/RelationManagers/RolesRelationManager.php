<?php

namespace App\Filament\Clusters\Access\Resources\Users\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use App\Enums\IconEnums;
use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Access\Resources\Users\UserResource;
use App\Policies\RolePolicy;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';
    protected static string | \BackedEnum | null $icon = IconEnums::ROLES->value;
    protected static ?string $title = 'Access roles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
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
            ->recordActions([
                DetachAction::make()
                    ->visible(fn ($record): bool => $this->ownerRecord->id !== Auth::user()->id && // Cannot detach own roles.
                        RolePolicy::attach(Auth::user())) 
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
