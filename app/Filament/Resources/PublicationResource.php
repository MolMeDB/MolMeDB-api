<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\PublicationResource\Pages;
use App\Filament\Resources\PublicationResource\RelationManagers;
use App\Models\Publication;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PublicationResource extends Resource
{
    protected static ?string $model = Publication::class;

    protected static ?string $navigationIcon = IconEnums::PUBLICATIONS->value;
    protected static ?string $navigationGroup = 'Data management';
    protected static ?int $navigationSort = 3;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Citation')
                    ->schema([
                        Forms\Components\TextArea::make('citation')
                            ->required()
                    ])
                    ->columns(1),
                FieldSet::make('Publication details')
                    ->schema([
                        Forms\Components\TextInput::make('pmid')
                            ->label('PMID')
                            ->hint('PubMed identifier'),
                        Forms\Components\TextInput::make('doi')
                            ->hint('e.g. 10.5281/zenodo.5121018')
                            ->label('DOI'),
                        Forms\Components\TextInput::make('title')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('authors')
                            ->columnSpanFull()
                            ->hint('List of authors delimited by commas [,]'),
                        Forms\Components\TextInput::make('journal'),
                        Forms\Components\TextInput::make('volume'),
                        Forms\Components\TextInput::make('issue'),
                        Forms\Components\TextInput::make('page'),
                        Forms\Components\TextInput::make('year')
                            ->numeric(),
                        Forms\Components\DatePicker::make('publicated_date')
                            ->label('Date of publication'),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->user()->id)
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (string $state) : string => Publication::enumType($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('citation')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('doi')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('pmid')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('authors')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('journal')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('publicated_date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Added by')
                    ->badge()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_passive_interactions')
                    ->label('Passive Int.')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_active_interactions')
                    ->label('Active Int.')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_substances')
                    ->label('Substances')
                    ->numeric()
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
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublications::route('/'),
            'create' => Pages\CreatePublication::route('/create'),
            'edit' => Pages\EditPublication::route('/{record}/edit'),
        ];
    }
}
