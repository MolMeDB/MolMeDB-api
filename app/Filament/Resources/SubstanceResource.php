<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\SubstanceResource\Pages;
use App\Filament\Resources\SubstanceResource\RelationManagers;
use App\Models\Substance;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Libraries;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;

class SubstanceResource extends Resource
{
    protected static ?string $model = Substance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Data management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('identifier')
                    ->hint('Will be generated automatically during saving data')
                    // Show only in create mode
                    // ->disabledOn('create')
                    ->disabled()
                    // ->hiddenOn('edit')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('smiles')
                    ->label('SMILES')
                    ->hint('Click the validate button to canonize SMILES')
                    ->required()
                    ->hiddenOn('edit')
                    ->validationMessages([
                        'required' => 'The SMILES is required.',
                    ])
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('molecular_weight')
                    ->hint('Calculated during the SMILES validation.')
                    ->readOnly()
                    ->disabledOn('edit')
                    ->numeric(),
                Forms\Components\TextInput::make('logp')
                    ->hint('Calculated during the SMILES validation.')
                    ->readOnly()
                    ->disabledOn('edit')
                    ->numeric(),
                Forms\Components\TextInput::make('inchikey')
                    ->label('InChIKey')
                    ->hint('Calculated during the SMILES validation.')
                    ->readOnly()
                    ->hiddenOn('edit')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('identifier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),
                Tables\Columns\TextColumn::make('molecular_weight')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('logp')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
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
            RelationManagers\IdentifiersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubstances::route('/'),
            'create' => Pages\CreateSubstance::route('/create'),
            'edit' => Pages\EditSubstance::route('/{record}/edit'),
        ];
    }
}
