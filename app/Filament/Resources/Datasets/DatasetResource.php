<?php

namespace App\Filament\Resources\Datasets;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\SharedRelationManagers\PublicationsRelationManager;
use App\Filament\Resources\SharedRelationManagers\IdentifiersRelationManager;
use App\Filament\Resources\SharedRelationManagers\InteractionsPassiveRelationManager;
use App\Filament\Resources\SharedRelationManagers\InteractionsActiveRelationManager;
use App\Filament\Resources\Datasets\Pages\ListDatasets;
use App\Filament\Resources\Datasets\Pages\CreateDataset;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Enums\IconEnums;
use App\Enums\PermissionEnums;
use App\Filament\Resources\DatasetResource\Pages;
use App\Filament\Resources\SharedRelationManagers;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components;
use Filament\Forms\Components\Component;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DatasetResource extends Resource
{
    protected static ?string $model = Dataset::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::DATASET->value;
    protected static string | \UnitEnum | null $navigationGroup = 'Interactions management';

    public static function form(Schema $schema): Schema
    {   
        return $schema
            ->components([
                Section::make('Basic assignment')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options(fn(?Dataset $record) => $record?->id ? Dataset::enumType() : Dataset::enumTypesSelectable())
                        ->required()
                        ->disabledOn('edit'),
                    Select::make('dataset_group_id')
                        ->relationship('group', 'name'),
                    Select::make('method_id')
                        ->options(fn(?Dataset $record) => Method::selectOptionsGrouped($record?->trashed()))
                        ->hidden(fn (?Dataset $record) => $record && $record->type == Dataset::TYPE_ACTIVE)
                        ->suffixAction(Action::make('show_method')
                            ->icon(IconEnums::VIEW->value)
                            ->url(fn (Get $get) => $get('method_id') ? MethodResource::getUrl('edit', ['record' => Method::withTrashed()->find($get('method_id'))]) : null)
                            ->openUrlInNewTab()
                        )
                        ->reactive()
                        ->required(),
                    Select::make('membrane_id')
                        ->options(fn(?Dataset $record) => Membrane::selectOptionsGrouped($record?->trashed()))
                        ->hidden(fn (?Dataset $record) => $record && $record->type == Dataset::TYPE_ACTIVE)
                        ->suffixAction(Action::make('show_membrane')
                            ->icon(IconEnums::VIEW->value)
                            ->url(fn (Get $get) => $get('membrane_id') ? MembraneResource::getUrl('edit', ['record' => Membrane::withTrashed()->find($get('membrane_id'))]) : null)
                            ->openUrlInNewTab()
                        )
                        ->reactive()
                        ->required(),  
                ]),
                Section::make('Description')
                ->columns(1)
                ->schema([
                    TextInput::make('name')
                        ->columnSpanFull()
                        ->maxLength(255)
                        ->hint('Maximum 255 characters.')
                        ->required(),
                    Textarea::make('comment')
                        ->columnSpanFull(),  
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        // dd(Dataset::find(38)->author?->name);

        return $table
            ->query(fn () => Dataset::query()->with(['activityLogs.causer']))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->color(fn(Dataset $record) => $record->trashed() ? 'danger' : null)
                    ->tooltip(fn(Dataset $record) => $record->trashed() ? 'Deleted record' : null)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state) : string => $state ? Dataset::enumType($state) : "Unknown")
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        Dataset::TYPE_ACTIVE => 'success',
                        Dataset::TYPE_PASSIVE => 'warning',
                        default => 'primary',
                    })
                    ->sortable(),
                TextColumn::make('group.name')
                    ->label('Group')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('method.abbreviation')
                    ->label('Method')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                TextColumn::make('membrane.abbreviation')
                    ->label('Membrane')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                TextColumn::make('comment')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author_name')
                    ->label('Author')
                    ->badge(),
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
                // Tables\Columns\TextColumn::make('interactions_count')
                //     ->label('# interactions')
                //     ->badge()
                //     ->color(fn (Dataset $dataset) => $dataset->trashed() ? 'danger' : 'primary')
                //     ->tooltip(fn (Dataset $dataset) => $dataset->trashed() ? 'All assigned interaction will be restored with dataset in case.' : '')
                //     ->alignCenter()
                //     ->getStateUsing(fn (Dataset $record) => match($record->type) {
                //         Dataset::TYPE_ACTIVE => $record->interactionsActive()->withTrashed()->count(),
                //         Dataset::TYPE_PASSIVE => $record->interactionsPassive()->withTrashed()->count(),
                //         default => "N/A"
                //     })
                
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(Dataset::enumType()),
                SelectFilter::make('membrane_id')
                    ->relationship('membrane', 'name')
                    ->label('Membrane')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('method_id')
                    ->relationship('method', 'name')
                    ->label('Method')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('author')
                ->label('Author')
                // ->options(User::permission(PermissionEnums::DATASET_EDIT->value)->pluck('name', 'id')->toArray())
                ->options(User::pluck('name', 'id')->toArray())
                ->modifyQueryUsing(function ($query, $state) {
                    if (array_key_exists('value', $state) && is_numeric($state['value'])) {
                        $query->whereHas('activityLogs', function ($q) use ($state) {
                            $q->where('causer_id', $state)
                                ->where('causer_type', User::class);
                        });
                    }
                }),
                TrashedFilter::make()
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make()
                    ->disabled(fn(Dataset $record) => !$record->isRestoreable()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ])
            ->emptyStateHeading('No datasets found.')
            ->emptyStateDescription('Start by uploading new dataset file.');
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
            PublicationsRelationManager::class,
            IdentifiersRelationManager::class,
            InteractionsPassiveRelationManager::class,
            InteractionsActiveRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDatasets::route('/'),
            'create' => CreateDataset::route('/create'),
            'edit' => EditDataset::route('/{record}/edit'),
        ];
    }
}
