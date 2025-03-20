<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategoryResource\RelationManagers;

use App\Enums\PermissionEnums;
use App\Filament\Resources\MethodResource;
use App\Models\Method;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
            ->recordTitleAttribute('abbreviation')
            ->columns([
                Tables\Columns\TextColumn::make('abbreviation')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->visible(fn ($record): bool => Auth::user()->hasPermissionTo(PermissionEnums::MEMBRANE_METHOD_EDIT))
                    ->recordSelectSearchColumns(['name', 'abbreviation'])
                    ->multiple()
                    ->recordSelect(fn (Select $select) => 
                        $select->placeholder('Please, select method')
                            ->searchable())
                    ->form(fn (AttachAction $action) => [
                        $action->getRecordSelect(),
                        Forms\Components\Hidden::make('model_type')
                            ->default(Method::class),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
