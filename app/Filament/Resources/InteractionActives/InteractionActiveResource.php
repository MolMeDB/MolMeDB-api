<?php

namespace App\Filament\Resources\InteractionActives;

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
use App\Filament\Resources\InteractionActives\Pages\ListInteractionActives;
use App\Filament\Resources\InteractionActives\Pages\CreateInteractionActive;
use App\Filament\Resources\InteractionActives\Pages\EditInteractionActive;
use App\Enums\IconEnums;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\InteractionActiveCategoryResource;
use App\Filament\Resources\Proteins\ProteinResource;
use App\Filament\Resources\Publications\PublicationResource;
use App\Filament\Resources\Structures\StructureResource;
use App\Models\InteractionActive;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class InteractionActiveResource extends Resource
{
    protected static ?string $model = InteractionActive::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::INTERACTIONS->value;
    protected static ?string $label = "Active interaction";
    protected static ?string $navigationLabel = "Active";
    protected static string | \UnitEnum | null $navigationGroup = 'Interactions management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic assignment')
                    ->schema([
                        Select::make('dataset_id')
                            ->relationship('dataset', 'name')
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
                            ->suffixAction(fn (Get $get) => Action::make('edit_structure')
                                ->url(fn () => $get('structure_id') ? StructureResource::getUrl('edit', ['record' => Structure::withTrashed()->find($get('structure_id'))]) : null)
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->label('Substance structure')
                            ->reactive()
                            ->required(),
                        Select::make('protein_id')
                            ->relationship('protein', 'uniprot_id', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->label('Protein target')
                            ->getOptionLabelFromRecordUsing(fn(Protein $record) => (
                                $record->trashed() ? ' (DELETED) ' : ''
                            ) . $record->uniprot_id)
                            ->suffixAction(fn (Get $get) => Action::make('edit_protein')
                                ->url(fn () => $get('structure_id') ? ProteinResource::getUrl('edit', ['record' => Protein::withTrashed()->find($get('protein_id'))]) : null)
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->reactive()
                            ->searchable()
                            ->required(),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(InteractionActive::enumCategories())
                            ->suffixAction(fn (Get $get) => Action::make('manage_category')
                                ->url(fn () => InteractionActiveCategoryResource::getUrl('categoryTree'))
                                ->icon(IconEnums::EDIT->value)
                                ->color('warning')
                                ->tooltip('Manage categories')
                                ->openUrlInNewTab())
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
                            ->maxLength(40)
                            ->hint('Maximum 40 characters.')
                            ->hintColor('warning'),
                    ]),
                Section::make('Interaction values')
                    ->columns(2)
                    ->schema([
                        TextInput::make('km')
                            ->numeric()
                            ->label('Km'),
                        TextInput::make('km_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Km accuracy'),
                        TextInput::make('ec50')
                            ->numeric()
                            ->label('EC50'),
                        TextInput::make('ec50_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('EC50 accuracy'),
                        TextInput::make('ki')
                            ->numeric()
                            ->label('Ki'),
                        TextInput::make('ki_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Ki accuracy'),
                        TextInput::make('ic50')
                            ->numeric()
                            ->label('IC50'),
                        TextInput::make('ic50_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('IC50 accuracy'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->description('List of active protein-structure interactions.')
            ->defaultSort('id', 'asc')
            ->paginated([25, 50, 100, 500])
            ->columns([
                TextColumn::make('structure.identifier')
                    ->sortable()
                    ->searchable()
                    ->color('warning'),
                TextColumn::make('protein.uniprot_id')
                    ->sortable()
                    ->label('Protein')
                    ->color('primary'),
                TextColumn::make('category.title')
                    ->sortable()
                    ->badge()
                    ->label('Category')
                    ->color('success')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('publication_id')
                    ->sortable()
                    ->label('Prim. reference')
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->publication?->getSelectTitle(), 30))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->wrap()
                    ->searchable()
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
                TextColumn::make('km')
                    ->label('Km')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->km_accuracy ? "+/- $record->km_accuracy" : null)
                    ->color(fn(Model $record) => $record->km_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ec50')
                    ->label('EC50')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->ec50_accuracy ? "+/- $record->ec50_accuracy" : null)
                    ->color(fn(Model $record) => $record->ec50_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ki')
                    ->label('Ki')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->ki_accuracy ? "+/- $record->ki_accuracy" : null)
                    ->color(fn(Model $record) => $record->ki_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ic50')
                    ->label('IC50')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->ic50_accuracy ? "+/- $record->ic50_accuracy" : null)
                    ->color(fn(Model $record) => $record->ic50_accuracy ? 'warning' : null)
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractionActives::route('/'),
            'create' => CreateInteractionActive::route('/create'),
            'edit' => EditInteractionActive::route('/{record}/edit'),
        ];
    }
}
