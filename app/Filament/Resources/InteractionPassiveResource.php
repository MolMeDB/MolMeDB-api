<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\InteractionPassiveResource\Pages;
use App\Filament\Resources\InteractionPassiveResource\RelationManagers;
use App\Models\Dataset;
use App\Models\InteractionPassive;
use App\Models\Publication;
use App\Models\Structure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class InteractionPassiveResource extends Resource
{
    protected static ?string $model = InteractionPassive::class;
    protected static ?string $navigationIcon = IconEnums::INTERACTIONS->value;
    protected static ?string $label = "Passive interaction";
    protected static ?string $navigationLabel = "Passive";
    protected static ?string $navigationGroup = 'Interactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic assignment')
                    ->schema([
                        Forms\Components\Select::make('dataset_id')
                            ->relationship('dataset', 'name', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->getOptionLabelFromRecordUsing(fn(Dataset $record) => "$record->name" . (
                                $record->trashed() ? ' (DELETED)' : ''
                            ))
                            ->label('Assigned to dataset')
                            ->hint('Dataset assignment cannot be changed.')
                            ->hintColor('danger')
                            ->disabled(),
                        Forms\Components\Select::make('structure_id')
                            ->relationship('structure', 'identifier', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn(Structure $record) => "$record->identifier" . (
                                $record->trashed() ? ' (DELETED)' : ''
                            ))
                            ->label('Substance structure')
                            ->suffixAction(fn (Get $get) => Forms\Components\Actions\Action::make('edit_structure')
                                ->url(fn () => $get('structure_id') ? StructureResource::getUrl('edit', ['record' => Structure::withTrashed()->find($get('structure_id'))]) : null)
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->reactive()
                            ->required(),
                        Forms\Components\Select::make('publication_id')
                            ->relationship('publication', 'citation', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->label('Primary reference')
                            ->getOptionLabelFromRecordUsing(fn(Publication $record) => (
                                $record->trashed() ? ' (DELETED) ' : ''
                            ) . $record->citation)
                            ->searchable()
                            ->suffixAction(fn (Get $get) => Forms\Components\Actions\Action::make('edit_publication')
                                ->url(fn () => PublicationResource::getUrl('edit', ['record' => Publication::withTrashed()->find($get('publication_id'))]))
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->reactive()
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->hint('Maximum 255 characters.')
                            ->hintColor('warning')
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\TextInput::make('temperature')
                            ->numeric()
                            ->maxValue(100)
                            ->minValue(-100)
                            ->label('T (°C)'),
                        Forms\Components\TextInput::make('ph')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(14)
                            ->formatStateUsing(fn ($state) => number_format($state, 1, '.', ''))
                            ->mutateDehydratedStateUsing(fn ($state) => round((float) $state, 1))
                            ->label('pH'),
                        Forms\Components\TextInput::make('charge')
                            ->label('Charge (Q)')
                            ->hint('Maximum 40 characters.')
                            ->hintColor('warning')
                            ->maxLength(40),
                    ]),
                Forms\Components\Section::make('Interaction values')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('x_min')
                            ->numeric()
                            ->label('Xmin'),
                        Forms\Components\TextInput::make('x_min_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Xmin accuracy'),
                        Forms\Components\TextInput::make('gpen')
                            ->numeric()
                            ->label('Gpen'),
                        Forms\Components\TextInput::make('gpen_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Gpen accuracy'),
                        Forms\Components\TextInput::make('gwat')
                            ->numeric()
                            ->label('Gwat'),
                        Forms\Components\TextInput::make('gwat_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Gwat accuracy'),
                        Forms\Components\TextInput::make('logk')
                            ->numeric()
                            ->label('LogK'),
                        Forms\Components\TextInput::make('logk_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('LogK accuracy'),
                        Forms\Components\TextInput::make('logperm')
                            ->numeric()
                            ->label('LogPerm'),
                        Forms\Components\TextInput::make('logperm_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('LogPerm accuracy'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'asc')
            ->description('List of passive structure-membrane interactions')
            ->paginated([25, 50, 100, 500])
            ->columns([
                Tables\Columns\TextColumn::make('structure.identifier')
                    ->sortable()
                    ->searchable()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('publication_id')
                    ->sortable()
                    ->label('Prim. reference')
                    ->tooltip(fn (Model $record) => $record->publication?->citation)
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->publication?->getSelectTitle(), 30))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('dataset.name')
                    ->sortable()
                    ->label('Dataset')
                    ->tooltip(fn (Model $record) => $record->dataset?->name)
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->dataset?->name, 30)),
                    // ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('note')
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn (Model $record) => $record->note)
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->note, 60))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('temperature')
                    ->numeric()
                    ->label('T (°C)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ph')
                    ->numeric()
                    ->label('pH')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('charge')
                    ->label('Q')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('x_min')
                    ->label('Xmin')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->x_min_accuracy ? "+/- $record->x_min_accuracy" : null)
                    ->color(fn(Model $record) => $record->x_min_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('gpen')
                    ->label('Gpen')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->gpen_accuracy ? "+/- $record->gpen_accuracy" : null)
                    ->color(fn(Model $record) => $record->gpen_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('gwat')
                    ->label('Gwat')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->gwat_accuracy ? "+/- $record->gwat_accuracy" : null)
                    ->color(fn(Model $record) => $record->gwat_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('logk')
                    ->label('LogK')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->logk_accuracy ? "+/- $record->logk_accuracy" : null)
                    ->color(fn(Model $record) => $record->logk_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('logperm')
                    ->label('LogPerm')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->logperm_accuracy ? "+/- $record->logperm_accuracy" : null)
                    ->color(fn(Model $record) => $record->logperm_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('compound_detail')
                    ->label('Structure')
                    ->icon(IconEnums::VIEW->value)
                    ->url(fn ($record) => StructureResource::getUrl('edit', ['record' => $record->structure])),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\RestoreAction::make()
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractionPassives::route('/'),
            'create' => Pages\CreateInteractionPassive::route('/create'),
            'edit' => Pages\EditInteractionPassive::route('/{record}/edit'),
        ];
    }
}
