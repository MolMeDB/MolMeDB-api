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
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Modules\References\CrossRef\CrossRef;
use Modules\References\EuropePMC\Enums\Query\SortBy;
use Modules\References\EuropePMC\Enums\Query\SortOrder;
use Modules\References\EuropePMC\Enums\Sources;
use Modules\References\EuropePMC\EuropePMC;

class PublicationResource extends Resource
{
    protected static ?string $model = Publication::class;

    protected static ?string $navigationIcon = IconEnums::PUBLICATIONS->value;
    protected static ?string $navigationGroup = 'Data management';

    public static function form(Form $form): Form
    {
        $europePMC = new EuropePMC();
        $crossRef = new CrossRef();
        return $form
            ->schema([
                Fieldset::make('Citation')
                    ->schema([
                        Forms\Components\Select::make('citation')
                            ->searchable()
                            ->reactive()
                            ->required()
                            ->getSearchResultsUsing(function (string $query) use ($europePMC) {
                                $result = $europePMC->search($query, SortBy::SCORE, SortOrder::DESC, 1, 10);
                                $options = [
                                    $query => '[UNLINKED] ' . $query
                                ];

                                if($result)
                                {
                                    foreach($result['records'] as $record)
                                    {
                                        if($record->id && $record->source)
                                        {
                                            $options[$record->id . '&&&' . $record->source->value] = $record->citation();
                                        }
                                    }
                                }
                                return $options;
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, $state) use ($europePMC) {
                                if(!str_contains($state, '&&&'))
                                {
                                    $set('identifier', '');
                                    $set('identifier_source', '');
                                    $set('doi', '');
                                    $set('title', '');
                                    $set('journal', '');
                                    $set('volume', '');
                                    $set('issue', '');
                                    $set('page', '');
                                    $set('year', '');
                                    $set('published_at', '');
                                    
                                    return;
                                }

                                $identifier = explode('&&&', $state);
                                $record = $europePMC->detail($identifier[0], Sources::tryFrom($identifier[1]));
                                
                                // Fill form
                                $set('identifier', $record->id);
                                $set('identifier_source', $record->source->value);
                                $set('doi', $record->doi);
                                $set('citation', $record->citation());
                                $set('title', $record->title);
                                $set('journal', $record->journal?->title);
                                $set('volume', $record->journal?->volume);
                                $set('issue', $record->journal?->issue);
                                $set('page', $record->pageInfo);
                                $set('year', $record->journal?->yearOfPublication);
                                // $set('published_at', $record->journal?->dateOfPublication);

                                Notification::make()
                                    ->title('Citation selected')
                                    ->body('All fields have been filled based on the Europe PMC record.')
                                    ->success()
                                    ->send();
                            })
                            ->unique(ignoreRecord: true)
                            ->filled()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                FieldSet::make('Publication details')
                    ->schema([
                        Forms\Components\TextInput::make('identifier')
                            ->label('Identifier')
                            ->maxLength(50)
                            ->suffixAction(
                                Action::make('validatePublicationRecord')
                                    ->icon(IconEnums::DOWNLOAD->value)
                                    ->requiresConfirmation()
                                    ->modalDescription('Are you sure you want to revalidate fields based on PMID? This will overwrite the existing values.')
                                    ->action(function (Set $set, Get $get, $state) use ($europePMC) {
                                        // Try to find the record
                                        $record = $europePMC->detail($state, Sources::tryFrom($get('identifier_source')));

                                        if(!$record)
                                        {
                                            // Not found?
                                            Notification::make()
                                                ->title('Record not found')
                                                ->body('The record could not be found in Europe PMC database. Check the identifier or try again with another identifier type.')
                                                ->danger()
                                                ->persistent()
                                                ->send();
                                            return;
                                        }

                                        // Fill form
                                        $set('citation', $record->citation());
                                        $set('doi', $record->doi);
                                        $set('title', $record->title);
                                        $set('journal', $record->journal?->title);
                                        $set('volume', $record->journal?->volume);
                                        $set('issue', $record->journal?->issue);
                                        $set('page', $record->pageInfo);
                                        $set('year', $record->journal?->yearOfPublication);
                                        $set('published_at', $record->journal?->dateOfPublication);

                                        Notification::make()
                                            ->title('Record found')
                                            ->body('All fields have been filled based on the Europe PMC record.')
                                            ->success()
                                            ->send();
                                    })
                            )
                            ->hint('Unique publication identifier'),
                        Forms\Components\Select::make('identifier_source')
                            ->label('Identifier type')
                            ->options(Sources::toSelect()),
                        Forms\Components\Select::make('doi')
                            ->hint('e.g. 10.5281/zenodo.5121018')
                            ->searchable()
                            ->reactive()
                            ->unique(ignoreRecord: true)
                            ->getSearchResultsUsing(function (string $query) use ($europePMC, $crossRef) {
                                $result = $europePMC->search($query, SortBy::SCORE, SortOrder::DESC, 1, 1);
                                $options = [];

                                if($result)
                                {
                                    foreach($result['records'] as $record)
                                    {
                                        if($record->id && $record->source)
                                        {
                                            $options[$record->id . '&&&' . $record->source->value] = $record->doi . ' [EuropePMC]';
                                        }
                                    }
                                }
                                else
                                {
                                    $result = $crossRef->work($query);
                                    if($result)
                                    {
                                        $options[$result->doi] = $result->doi . ' [CrossRef]';
                                    }
                                }
                                return $options;
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, $state) use ($europePMC, $crossRef) {
                                if(!str_contains($state, '&&&')){
                                    if(!$state) return;

                                    $record = $crossRef->work($state);
                                    if(!$record) return;
                                }
                                else
                                {
                                    $identifier = explode('&&&', $state);
                                    $record = $europePMC->detail($identifier[0], Sources::tryFrom($identifier[1]));
                                }

                                if(!$record) return;
                                
                                // Fill form
                                $set('identifier', $record->id);
                                $set('identifier_source', $record->source?->value);
                                $set('citation', $record->citation());
                                $set('title', $record->title);
                                $set('journal', $record->journal?->title);
                                $set('volume', $record->journal?->volume);
                                $set('issue', $record->journal?->issue);
                                $set('page', $record->pageInfo);
                                $set('year', $record->journal?->yearOfPublication);
                                $set('published_at', $record->journal?->dateOfPublication);

                                Notification::make()
                                    ->title('DOI selected')
                                    ->body('All fields have been filled based on the Europe PMC record.')
                                    ->success()
                                    ->send();
                            })
                            ->prefix('https://doi.org/')
                            ->suffixAction(
                                Action::make('openDoi')
                                    ->icon(IconEnums::NEWTAB->value)
                                    ->openUrlInNewTab()
                                    ->url(fn ($state) => 'https://doi.org/' . $state)
                            )
                            ->label('DOI')
                            ->requiredWithAll(['identifier', 'identifier_source'])
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->maxLength(512)
                            ->disabled()
                            ->hint('Obtained automatically from EuropePMC.')
                            ->hintColor('warning') 
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('journal')
                            ->disabled()
                            ->hint('Obtained automatically from EuropePMC.')
                            ->hintColor('warning') 
                            ->maxLength(256),
                        Forms\Components\TextInput::make('volume')
                            ->disabled()
                            ->hint('Obtained automatically from EuropePMC.')
                            ->hintColor('warning') 
                            ->maxLength(50),
                        Forms\Components\TextInput::make('issue')
                            ->disabled()
                            ->hint('Obtained automatically from EuropePMC.')
                            ->hintColor('warning') 
                            ->maxLength(50),
                        Forms\Components\TextInput::make('page')
                            ->disabled()
                            ->hint('Obtained automatically from EuropePMC.')
                            ->hintColor('warning') 
                            ->maxLength(50),
                        Forms\Components\TextInput::make('year')
                            ->disabled()
                            ->hint('Obtained automatically from EuropePMC.')
                            ->hintColor('warning') 
                            ->numeric()
                            ->minValue(1800)
                            ->maxValue(date('Y')),
                        Forms\Components\DatePicker::make('published_at')
                            ->disabled()
                            ->hint('Obtained automatically from EuropePMC.')
                            ->hintColor('warning') 
                            ->minDate('1800-01-01')
                            ->maxDate(date('Y-m-d'))
                            ->label('Date of publication'),
                        Forms\Components\Placeholder::make('validated_at')
                            ->label('Last validation at')
                            ->columnSpanFull()
                            ->hiddenOn('create')
                            ->content(fn (?\App\Models\Publication $record): ?string => $record?->validated_at?->isoFormat('LLLL') ?? "Never"),
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
                Tables\Columns\TextColumn::make('author.name')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('doi')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('identifier')
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('publicated_date')
                    ->since()
                    ->dateTimeTooltip()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
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
            SharedRelationManagers\FileRelationManager::class

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
