<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategoryResource;
use App\Filament\Resources\InteractionActiveResource\Pages;
use App\Filament\Resources\InteractionActiveResource\RelationManagers;
use App\Models\Category;
use App\Models\InteractionActive;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use BaconQrCode\Encoder\MaskUtil;
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

class InteractionActiveResource extends Resource
{
    protected static ?string $model = InteractionActive::class;
    protected static ?string $navigationIcon = IconEnums::INTERACTIONS->value;
    protected static ?string $label = "Active interaction";
    protected static ?string $navigationLabel = "Active";
    protected static ?string $navigationGroup = 'Interactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic assignment')
                    ->schema([
                        Forms\Components\Select::make('dataset_id')
                            ->relationship('dataset', 'name')
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
                            ->suffixAction(fn (Get $get) => Forms\Components\Actions\Action::make('edit_structure')
                                ->url(fn () => $get('structure_id') ? StructureResource::getUrl('edit', ['record' => Structure::withTrashed()->find($get('structure_id'))]) : null)
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->label('Substance structure')
                            ->reactive()
                            ->required(),
                        Forms\Components\Select::make('protein_id')
                            ->relationship('protein', 'uniprot_id', fn ($query, $record) => $record->trashed() ? $query->withTrashed() : $query)
                            ->label('Protein target')
                            ->getOptionLabelFromRecordUsing(fn(Protein $record) => (
                                $record->trashed() ? ' (DELETED) ' : ''
                            ) . $record->uniprot_id)
                            ->suffixAction(fn (Get $get) => Forms\Components\Actions\Action::make('edit_protein')
                                ->url(fn () => $get('structure_id') ? ProteinResource::getUrl('edit', ['record' => Protein::withTrashed()->find($get('protein_id'))]) : null)
                                ->icon(IconEnums::VIEW->value)
                                ->tooltip('Show detail')
                                ->openUrlInNewTab())
                            ->reactive()
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(InteractionActive::enumCategories())
                            ->suffixAction(fn (Get $get) => Forms\Components\Actions\Action::make('manage_category')
                                ->url(fn () => InteractionActiveCategoryResource::getUrl('categoryTree'))
                                ->icon(IconEnums::EDIT->value)
                                ->color('warning')
                                ->tooltip('Manage categories')
                                ->openUrlInNewTab())
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
                            ->formatStateUsing(fn ($state) => $state === null ? null : number_format($state, 1, '.', ''))
                            ->mutateDehydratedStateUsing(fn ($state) => $state === null ? null : round((float) $state, 1))
                            ->label('pH'),
                        Forms\Components\TextInput::make('charge')
                            ->label('Charge (Q)')
                            ->maxLength(40)
                            ->hint('Maximum 40 characters.')
                            ->hintColor('warning'),
                    ]),
                Forms\Components\Section::make('Interaction values')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('km')
                            ->numeric()
                            ->label('Km'),
                        Forms\Components\TextInput::make('km_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Km accuracy'),
                        Forms\Components\TextInput::make('ec50')
                            ->numeric()
                            ->label('EC50'),
                        Forms\Components\TextInput::make('ec50_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('EC50 accuracy'),
                        Forms\Components\TextInput::make('ki')
                            ->numeric()
                            ->label('Ki'),
                        Forms\Components\TextInput::make('ki_accuracy')
                            ->numeric()
                            ->prefix('+/-')
                            ->label('Ki accuracy'),
                        Forms\Components\TextInput::make('ic50')
                            ->numeric()
                            ->label('IC50'),
                        Forms\Components\TextInput::make('ic50_accuracy')
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
                Tables\Columns\TextColumn::make('structure.identifier')
                    ->sortable()
                    ->searchable()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('protein.uniprot_id')
                    ->sortable()
                    ->label('Protein')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('category.title')
                    ->sortable()
                    ->badge()
                    ->label('Category')
                    ->color('success')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('publication_id')
                    ->sortable()
                    ->label('Prim. reference')
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->publication?->getSelectTitle(), 30))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('note')
                    ->wrap()
                    ->searchable()
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
                Tables\Columns\TextColumn::make('km')
                    ->label('Km')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->km_accuracy ? "+/- $record->km_accuracy" : null)
                    ->color(fn(Model $record) => $record->km_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ec50')
                    ->label('EC50')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->ec50_accuracy ? "+/- $record->ec50_accuracy" : null)
                    ->color(fn(Model $record) => $record->ec50_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ki')
                    ->label('Ki')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->ki_accuracy ? "+/- $record->ki_accuracy" : null)
                    ->color(fn(Model $record) => $record->ki_accuracy ? 'warning' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ic50')
                    ->label('IC50')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (Model $record) => $record->ic50_accuracy ? "+/- $record->ic50_accuracy" : null)
                    ->color(fn(Model $record) => $record->ic50_accuracy ? 'warning' : null)
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
            'index' => Pages\ListInteractionActives::route('/'),
            'create' => Pages\CreateInteractionActive::route('/create'),
            'edit' => Pages\EditInteractionActive::route('/{record}/edit'),
        ];
    }
}
