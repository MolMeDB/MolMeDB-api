<?php

namespace App\Filament\Resources\InteractionPassives;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\InteractionPassives\Pages\ListInteractionPassives;
use App\Filament\Resources\InteractionPassives\Pages\CreateInteractionPassive;
use App\Filament\Resources\InteractionPassives\Pages\EditInteractionPassive;
use App\Enums\IconEnums;
use App\Filament\Resources\Publications\PublicationResource;
use App\Filament\Resources\Structures\StructureResource;
use App\Models\Dataset;
use App\Models\InteractionPassive;
use App\Models\Publication;
use App\Models\Structure;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class InteractionPassiveResource extends Resource
{
    protected static ?string $model = InteractionPassive::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::INTERACTIONS->value;
    protected static ?string $label = "Passive interaction";
    protected static ?string $navigationLabel = "Passive";
    protected static string | \UnitEnum | null $navigationGroup = 'Interactions management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic assignment')
                    ->schema([
                        Select::make('dataset_id')
                            ->relationship('dataset', 'name', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->getOptionLabelFromRecordUsing(fn(Dataset $record) => "$record->name" . (
                                $record->trashed() ? ' (DELETED)' : ''
                            ))
                            ->label('Assigned to dataset')
                            ->hint('Dataset assignment cannot be changed.')
                            ->hintColor('danger')
                            ->disabled(),
                        Select::make('structure_id')
                            ->relationship('structure', 'identifier', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn(Structure $record) => "$record->identifier" . (
                                $record->trashed() ? ' (DELETED)' : ''
                            ))
                            ->label('Substance structure')
                            ->suffixAction(fn (Get $get) => Action::make('edit_structure')
                                ->url(fn () => $get('structure_id') ? StructureResource::getUrl('edit', ['record' => Structure::withTrashed()->find($get('structure_id'))]) : null)
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->reactive()
                            ->required(),
                        Select::make('publication_id')
                            ->relationship('publication', 'citation', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->label('Primary reference')
                            ->getOptionLabelFromRecordUsing(fn(Publication $record) => (
                                $record->trashed() ? ' (DELETED) ' : ''
                            ) . $record->citation)
                            ->searchable()
                            ->suffixAction(fn (Get $get) => Action::make('edit_publication')
                                ->url(fn () => PublicationResource::getUrl('edit', ['record' => Publication::withTrashed()->find($get('publication_id'))]))
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->reactive()
                            ->required(),
                        Textarea::make('note')
                            ->hint('Maximum 255 characters.')
                            ->hintColor('warning')
                            ->maxLength(255),
                    ]),
                Section::make('Conditions')
                    ->schema([
                        TextInput::make('temperature')
                            ->numeric()
                            ->maxValue(100)
                            ->minValue(-100)
                            ->label('T (°C)'),
                        TextInput::make('ph')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(14)
                            ->formatStateUsing(fn ($state) => $state === null ? null : number_format($state, 1, '.', ''))
                            ->mutateDehydratedStateUsing(fn ($state) => $state === null ? null : round((float) $state, 1))
                            ->label('pH'),
                        TextInput::make('charge')
                            ->label('Charge (Q)')
                            ->hint('Maximum 40 characters.')
                            ->hintColor('warning')
                            ->maxLength(40),
                    ]),
                Section::make('Interaction values')
                    ->columns(2)
                    ->schema([
                        TextInput::make('x_min')
                            ->numeric()
                            ->label('Xmin'),
                        TextInput::make('x_min_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Xmin accuracy'),
                        TextInput::make('gpen')
                            ->numeric()
                            ->label('Gpen'),
                        TextInput::make('gpen_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Gpen accuracy'),
                        TextInput::make('gwat')
                            ->numeric()
                            ->label('Gwat'),
                        TextInput::make('gwat_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Gwat accuracy'),
                        TextInput::make('logk')
                            ->numeric()
                            ->label('LogK'),
                        TextInput::make('logk_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('LogK accuracy'),
                        TextInput::make('logperm')
                            ->numeric()
                            ->label('LogPerm'),
                        TextInput::make('logperm_accuracy')
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
                TextColumn::make('structure.identifier')
                    ->sortable()
                    ->searchable()
                    ->color('warning'),
                TextColumn::make('publication_id')
                    ->sortable()
                    ->label('Prim. reference')
                    ->tooltip(fn (Model $record) => $record->publication?->citation)
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->publication?->getSelectTitle(), 30))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dataset.name')
                    ->sortable()
                    ->label('Dataset')
                    ->tooltip(fn (Model $record) => $record->dataset?->name)
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->dataset?->name, 30)),
                    // ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn (Model $record) => $record->note)
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->note, 60))
                    ->toggleable(),
                TextColumn::make('temperature')
                    ->numeric()
                    ->label('T (°C)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ph')
                    ->numeric()
                    ->label('pH')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('charge')
                    ->label('Q')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('x_min')
                    ->label('Xmin')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->x_min_accuracy ? "+/- $record->x_min_accuracy" : null)
                    ->color(fn(Model $record) => $record->x_min_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gpen')
                    ->label('Gpen')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->gpen_accuracy ? "+/- $record->gpen_accuracy" : null)
                    ->color(fn(Model $record) => $record->gpen_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gwat')
                    ->label('Gwat')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->gwat_accuracy ? "+/- $record->gwat_accuracy" : null)
                    ->color(fn(Model $record) => $record->gwat_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('logk')
                    ->label('LogK')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->logk_accuracy ? "+/- $record->logk_accuracy" : null)
                    ->color(fn(Model $record) => $record->logk_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('logperm')
                    ->label('LogPerm')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->logperm_accuracy ? "+/- $record->logperm_accuracy" : null)
                    ->color(fn(Model $record) => $record->logperm_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                Action::make('compound_detail')
                    ->label('Structure')
                    ->icon(IconEnums::VIEW->value)
                    ->url(fn ($record) => StructureResource::getUrl('edit', ['record' => $record->structure])),
                EditAction::make()
                    ->color('warning'),
                RestoreAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListInteractionPassives::route('/'),
            'create' => CreateInteractionPassive::route('/create'),
            'edit' => EditInteractionPassive::route('/{record}/edit'),
        ];
    }
}
