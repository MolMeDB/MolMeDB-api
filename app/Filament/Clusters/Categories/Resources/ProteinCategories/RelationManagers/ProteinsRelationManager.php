<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategories\RelationManagers;

use App\Enums\PermissionEnums;
use App\Filament\Resources\Proteins\ProteinResource;
use App\Models\Protein;
use Filament\Actions\AttachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProteinsRelationManager extends RelationManager
{
    protected static string $relationship = 'proteins';

    public function form(Schema $schema): Schema
    {
        return ProteinResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('uniprot_id')
            ->columns([
                TextColumn::make('uniprot_id')
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->visible(fn ($record): bool => Auth::user()->hasPermissionTo(PermissionEnums::PROTEIN_EDIT))
                    ->recordSelectSearchColumns(['name', 'uniprot_id'])
                    ->multiple()
                    ->recordSelect(fn (Select $select) => $select->placeholder('Please, select protein')
                        ->searchable())
                    ->schema(fn (AttachAction $action) => [
                        $action->getRecordSelect(),
                        Hidden::make('model_type')
                            ->default(Protein::class),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
