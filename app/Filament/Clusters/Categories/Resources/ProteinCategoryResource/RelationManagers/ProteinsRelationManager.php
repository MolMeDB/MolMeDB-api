<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategoryResource\RelationManagers;

use App\Enums\PermissionEnums;
use App\Filament\Resources\ProteinResource;
use App\Models\Protein;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProteinsRelationManager extends RelationManager
{
    protected static string $relationship = 'proteins';

    public function form(Form $form): Form
    {
        return ProteinResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('uniprot_id')
            ->columns([
                Tables\Columns\TextColumn::make('uniprot_id')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->visible(fn ($record): bool => Auth::user()->hasPermissionTo(PermissionEnums::MEMBRANE_METHOD_EDIT)) // TODO
                    ->recordSelectSearchColumns(['name', 'uniprot_id'])
                    ->multiple()
                    ->recordSelect(fn (Select $select) => 
                        $select->placeholder('Please, select protein')
                            ->searchable())
                    ->form(fn (AttachAction $action) => [
                        $action->getRecordSelect(),
                        Forms\Components\Hidden::make('model_type')
                            ->default(Protein::class),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
