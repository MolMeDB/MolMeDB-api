<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\PublicationResource\Pages;
use App\Filament\Resources\PublicationResource\RelationManagers\AuthorRelationManager;
use App\Filament\Resources\SharedRelationManagers;
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
                            ->maxLength(1024)
                            ->hint('Maximum 1024 characters.')
                            ->hintColor('warning')
                            ->required()
                    ])
                    ->columns(1),
                FieldSet::make('Publication details')
                    ->schema([
                        Forms\Components\TextInput::make('pmid')
                            ->label('PMID')
                            ->maxLength(50)
                            ->hint('PubMed identifier'),
                        Forms\Components\TextInput::make('doi')
                            ->hint('e.g. 10.5281/zenodo.5121018')
                            ->maxLength(128)
                            ->label('DOI'),
                        Forms\Components\TextInput::make('title')
                            ->maxLength(512)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('journal')
                            ->maxLength(256),
                        Forms\Components\TextInput::make('volume')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('issue')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('page')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('year')
                            ->numeric()
                            ->minValue(1800)
                            ->maxValue(date('Y')),
                        Forms\Components\DatePicker::make('publicated_date')
                            ->minDate('1800-01-01')
                            ->maxDate(date('Y-m-d'))
                            ->label('Date of publication'),
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
                Tables\Columns\TextColumn::make('authors.name')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->listWithLineBreaks(),
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
                Tables\Filters\TrashedFilter::make()
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
            AuthorRelationManager::class,
            SharedRelationManagers\DatasetsRelationManager::class,
            SharedRelationManagers\InteractionsActiveRelationManager::class,
            SharedRelationManagers\InteractionsPassiveRelationManager::class,
            SharedRelationManagers\MethodsRelationManager::class,
            SharedRelationManagers\MembranesRelationManager::class,

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
