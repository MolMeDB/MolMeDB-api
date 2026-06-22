<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Models\BaseModel;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Logs';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    protected static ?string $modelLabel = 'Log';

    protected static ?string $pluralModelLabel = 'Logs';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Log')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID')
                            ->badge(),
                        TextEntry::make('log_name')
                            ->label('Log')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('event')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Updated'),
                        TextEntry::make('batch_uuid')
                            ->copyable()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Subject and causer')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subject')
                            ->label('Subject')
                            ->state(fn (Activity $record): string => static::formatMorph($record->subject_type, $record->subject_id))
                            ->copyable(),
                        TextEntry::make('causer')
                            ->label('Causer')
                            ->state(fn (Activity $record): string => static::formatMorph($record->causer_type, $record->causer_id))
                            ->copyable(),
                    ]),
                Section::make('Properties')
                    ->schema([
                        TextEntry::make('properties')
                            ->state(fn (Activity $record): string => static::formatProperties($record->properties))
                            ->placeholder('No properties')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->color(fn (?string $state): string => $state === BaseModel::ACTIVITY_LOG_SYSTEM ? 'danger' : 'gray')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('event')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('description')
                    ->limit(80)
                    ->tooltip(fn (Activity $record): string => $record->description)
                    ->searchable(),
                TextColumn::make('subject')
                    ->state(fn (Activity $record): string => static::formatMorph($record->subject_type, $record->subject_id))
                    ->toggleable(),
                TextColumn::make('causer')
                    ->state(fn (Activity $record): string => static::formatMorph($record->causer_type, $record->causer_id))
                    ->toggleable(),
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options(fn (): array => static::distinctOptions('log_name'))
                    ->default(BaseModel::ACTIVITY_LOG_SYSTEM)
                    ->searchable(),
                SelectFilter::make('event')
                    ->options(fn (): array => static::distinctOptions('event'))
                    ->searchable(),
                SelectFilter::make('subject_type')
                    ->label('Subject')
                    ->options(fn (): array => static::classOptions('subject_type'))
                    ->searchable(),
                SelectFilter::make('causer_type')
                    ->label('Causer')
                    ->options(fn (): array => static::classOptions('causer_type'))
                    ->searchable(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['causer', 'subject']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function distinctOptions(string $column): array
    {
        return Activity::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function classOptions(string $column): array
    {
        return collect(static::distinctOptions($column))
            ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
            ->all();
    }

    public static function formatMorph(?string $type, ?int $id): string
    {
        if (blank($type) || blank($id)) {
            return '-';
        }

        return class_basename($type).' #'.$id;
    }

    /**
     * @return array<string, mixed>
     */
    public static function propertiesToArray(mixed $properties): array
    {
        if ($properties instanceof Collection) {
            return $properties->all();
        }

        if (is_array($properties)) {
            return $properties;
        }

        return [];
    }

    public static function formatProperties(mixed $properties): string
    {
        $properties = static::propertiesToArray($properties);

        if ($properties === []) {
            return '';
        }

        return json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
