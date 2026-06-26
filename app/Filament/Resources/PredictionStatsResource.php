<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PredictionStatsResource\Pages;
use BackedEnum;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\PredictionStat;

class PredictionStatsResource extends Resource
{
    protected static ?string $model = PredictionStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Predictions';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Prediction stats';

    protected static ?string $modelLabel = 'Prediction stat';

    protected static ?string $pluralModelLabel = 'Prediction stats';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Snapshot')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('stats_date')
                            ->label('Date')
                            ->date()
                            ->badge(),
                        Infolists\Components\TextEntry::make('server_url')
                            ->label('Server URL')
                            ->copyable()
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('fetched_at')
                            ->label('Fetched')
                            ->dateTime()
                            ->since()
                            ->dateTimeTooltip(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime(),
                    ]),
                Section::make('Remote API JSON')
                    ->schema([
                        Infolists\Components\TextEntry::make('payload')
                            ->label('Payload')
                            ->state(fn (PredictionStat $record): string => json_encode(
                                $record->payload,
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                            ) ?: '{}')
                            ->fontFamily('mono')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('stats_date', 'desc')
            ->paginationPageOptions([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('stats_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->badge(),
                TextColumn::make('server_url')
                    ->label('Server URL')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(fn (PredictionStat $record): string => $record->server_url),
                TextColumn::make('fetched_at')
                    ->label('Fetched')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([]),
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
            'index' => Pages\ListPredictionStats::route('/'),
            'view' => Pages\ViewPredictionStats::route('/{record}'),
        ];
    }
}
