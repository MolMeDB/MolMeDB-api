<?php

namespace App\Filament\Resources;

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
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DatasetResource extends Resource
{
    protected static ?string $model = Dataset::class;
    protected static ?string $navigationIcon = IconEnums::DATASET->value;
    protected static ?string $navigationGroup = 'Data management';

    public static function form(Form $form): Form
    {   
        return $form
            ->schema([
                Forms\Components\Section::make('Basic assignment')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options(Dataset::enumType())
                        ->disabled()
                        ->required(),
                    Forms\Components\Select::make('dataset_group_id')
                        ->relationship('group', 'name'),
                    Forms\Components\Select::make('method_id')
                        ->options(fn(Dataset $record) => Method::selectOptionsGrouped($record->trashed()))
                        ->hidden(fn (Dataset $record) => $record->type == Dataset::TYPE_ACTIVE)
                        ->suffixAction(Components\Actions\Action::make('show_method')
                            ->icon(IconEnums::VIEW->value)
                            ->url(fn (Get $get) => $get('method_id') ? MethodResource::getUrl('edit', ['record' => Method::withTrashed()->find($get('method_id'))]) : null)
                            ->openUrlInNewTab()
                        )
                        ->reactive()
                        ->required(),
                    Forms\Components\Select::make('membrane_id')
                        ->options(fn(Dataset $record) => Membrane::selectOptionsGrouped($record->trashed()))
                        ->hidden(fn (Dataset $record) => $record->type == Dataset::TYPE_ACTIVE)
                        ->suffixAction(Components\Actions\Action::make('show_membrane')
                            ->icon(IconEnums::VIEW->value)
                            ->url(fn (Get $get) => $get('membrane_id') ? MembraneResource::getUrl('edit', ['record' => Membrane::withTrashed()->find($get('membrane_id'))]) : null)
                            ->openUrlInNewTab()
                        )
                        ->reactive()
                        ->required(),  
                ]),
                Forms\Components\Section::make('Description')
                ->columns(1)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->columnSpanFull()
                        ->maxLength(255)
                        ->hint('Maximum 255 characters.')
                        ->required(),
                    Forms\Components\Textarea::make('comment')
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
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->color(fn(Dataset $record) => $record->trashed() ? 'danger' : null)
                    ->tooltip(fn(Dataset $record) => $record->trashed() ? 'Deleted record' : null)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (string $state) : string => $state ? Dataset::enumType($state) : "Unknown")
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        Dataset::TYPE_ACTIVE => 'success',
                        Dataset::TYPE_PASSIVE => 'warning',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('method.abbreviation')
                    ->label('Method')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('membrane.abbreviation')
                    ->label('Membrane')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('author_name')
                    ->label('Author')
                    ->badge(),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options(Dataset::enumType()),
                Tables\Filters\SelectFilter::make('membrane_id')
                    ->relationship('membrane', 'name')
                    ->label('Membrane')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('method_id')
                    ->relationship('method', 'name')
                    ->label('Method')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('author')
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
                Tables\Filters\TrashedFilter::make()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->disabled(fn(Dataset $record) => !$record->isRestoreable()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            SharedRelationManagers\PublicationsRelationManager::class,
            SharedRelationManagers\IdentifiersRelationManager::class,
            SharedRelationManagers\InteractionsPassiveRelationManager::class,
            SharedRelationManagers\InteractionsActiveRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDatasets::route('/'),
            'create' => Pages\CreateDataset::route('/create'),
            'edit' => Pages\EditDataset::route('/{record}/edit'),
        ];
    }
}
