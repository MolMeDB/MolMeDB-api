<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\ProteinResource\Pages;
use App\Filament\Resources\ProteinResource\RelationManagers\IdentifiersRelationManager;
use App\Filament\Resources\SharedRelationManagers;
use App\Models\Protein;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProteinResource extends Resource
{
    protected static ?string $model = Protein::class;
    protected static ?string $navigationIcon = IconEnums::PROTEIN->value;
    protected static ?string $navigationGroup = 'Data management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Basic information')
                    ->schema([
                        Components\TextInput::make('uniprot_id')
                            ->label('Uniprot ID')
                            ->required()
                            ->maxLength(50)
                            ->hint('Maximum 50 characters.')
                            ->hintColor('danger'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('uniprot_id')
                    ->label('Uniprot ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('interactionsActive_count')
                    ->getStateUsing(fn ($record) => $record->interactionsActive()->count())
                    ->label('Total interactions')
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


    public static function getRelations(): array
    {
        return [
            IdentifiersRelationManager::class,
            SharedRelationManagers\InteractionsActiveRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProteins::route('/'),
            'create' => Pages\CreateProtein::route('/create'),
            'edit' => Pages\EditProtein::route('/{record}/edit'),
        ];
    }
}
