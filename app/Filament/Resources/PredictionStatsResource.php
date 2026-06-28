<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PredictionStatsResource\Pages;
use BackedEnum;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\Prediction;
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
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ─── Snapshot meta ────────────────────────────────────────────────
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

            // ─── Totals ───────────────────────────────────────────────────────
            Section::make('Totals')
                ->schema([
                    SchemaView::make('filament.prediction-stats.totals-charts')
                        ->columnSpanFull(),
                ]),

            // ─── Queue depth ──────────────────────────────────────────────────
            Section::make('Queue depth')
                ->columns(5)
                ->schema([
                    Infolists\Components\TextEntry::make('queue_rdkit')
                        ->label('RDKit')
                        ->state(fn (PredictionStat $record): string => (string) ($record->payload['queue']['rdkit'] ?? 0))
                        ->badge()
                        ->color(fn (string $state): string => (int) $state > 0 ? 'warning' : 'gray'),
                    Infolists\Components\TextEntry::make('queue_conformers')
                        ->label('Conformers')
                        ->state(fn (PredictionStat $record): string => (string) ($record->payload['queue']['conformers'] ?? 0))
                        ->badge()
                        ->color(fn (string $state): string => (int) $state > 0 ? 'warning' : 'gray'),
                    Infolists\Components\TextEntry::make('queue_turbomole')
                        ->label('Opt. Turbomole')
                        ->state(fn (PredictionStat $record): string => (string) ($record->payload['queue']['optimization-turbomole'] ?? 0))
                        ->badge()
                        ->color(fn (string $state): string => (int) $state > 0 ? 'warning' : 'gray'),
                    Infolists\Components\TextEntry::make('queue_orca')
                        ->label('Opt. Orca')
                        ->state(fn (PredictionStat $record): string => (string) ($record->payload['queue']['optimization-orca'] ?? 0))
                        ->badge()
                        ->color(fn (string $state): string => (int) $state > 0 ? 'warning' : 'gray'),
                    Infolists\Components\TextEntry::make('queue_cosmo')
                        ->label('COSMO')
                        ->state(fn (PredictionStat $record): string => (string) ($record->payload['queue']['cosmo'] ?? 0))
                        ->badge()
                        ->color(fn (string $state): string => (int) $state > 0 ? 'warning' : 'gray'),
                ]),

            // ─── Period statistics ─────────────────────────────────────────────
            Section::make('Statistics by period')
                ->schema([
                    SchemaView::make('filament.prediction-stats.period-stats-charts')
                        ->columnSpanFull(),
                ]),

            // ─── Running jobs ──────────────────────────────────────────────────
            Section::make('Running jobs')
                ->schema([
                    Infolists\Components\TextEntry::make('running_jobs')
                        ->label('')
                        ->state(function (PredictionStat $record): string {
                            $payload = $record->payload;

                            $runningCosmo = $payload['running']['cosmo'] ?? [];
                            $runningConformers = $payload['running']['conformer_steps'] ?? [];
                            $runningMolecules = $payload['running']['molecule_steps'] ?? [];

                            $calcIds = array_filter(array_column($runningCosmo, 'calculation_id'));
                            $moleculeIds = array_filter(array_unique(array_merge(
                                array_column($runningCosmo, 'molecule_id'),
                                array_column($runningConformers, 'molecule_id'),
                                array_column($runningMolecules, 'molecule_id'),
                            )));

                            $predsByCalcId = filled($calcIds)
                                ? Prediction::whereIn('remote_calculation_id', $calcIds)->get()->keyBy('remote_calculation_id')
                                : collect();

                            $predsByMoleculeId = filled($moleculeIds)
                                ? Prediction::whereIn('remote_molecule_id', $moleculeIds)->get()->keyBy('remote_molecule_id')
                                : collect();

                            return view('filament.prediction-stats.running-jobs', [
                                'runningCosmo' => $runningCosmo,
                                'runningConformers' => $runningConformers,
                                'runningMoleculeSteps' => $runningMolecules,
                                'predsByCalcId' => $predsByCalcId,
                                'predsByMoleculeId' => $predsByMoleculeId,
                            ])->render();
                        })
                        ->html()
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
            ->filters([])
            ->recordActions([
                Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPredictionStats::route('/'),
            'view' => Pages\ViewPredictionStats::route('/{record}'),
        ];
    }
}
