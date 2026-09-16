<?php

namespace App\Filament\Resources\PredictionDatasets\RelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\Predictions\PredictionResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;
use Throwable;

class PredictionsRelationManager extends RelationManager
{
    protected static string $relationship = 'predictions';

    protected static string|\BackedEnum|null $icon = IconEnums::PREDICTION->value;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->predictions()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictions()->count() > 0 ? 'primary' : 'danger';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(PredictionResource::table($table)->getColumns())
            ->filters([])
            ->headerActions([
                Action::make('requeue_failed')
                    ->label('Requeue all failed')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will requeue all failed predictions in this dataset.')
                    ->visible(fn () => $this->getOwnerRecord()
                        ->predictions()
                        ->whereIn('state', Prediction::failedStates())
                        ->exists())
                    ->action(function () {
                        $client = app(RemotePredictionClient::class);

                        try {
                            $client->ensureValidToken();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Token error: '.$e->getMessage())
                                ->send();

                            return;
                        }

                        $requeued = 0;
                        $failed = 0;

                        $this->getOwnerRecord()
                            ->predictions()
                            ->whereIn('state', Prediction::failedStates())
                            ->limit(max(1, (int) config('prediction-workers.remote.worker.admin_bulk_limit', 20)))
                            ->get()
                            ->each(function (Prediction $prediction) use ($client, &$requeued, &$failed): void {
                                try {
                                    $prediction->requeueAndStoreRemotePrediction(client: $client);
                                    $requeued++;
                                } catch (Throwable $e) {
                                    $failed++;
                                }
                            });

                        Notification::make()
                            ->success()
                            ->title("Requeued {$requeued} prediction(s)".($failed > 0 ? ", {$failed} failed" : ''))
                            ->send();
                    }),
                Action::make('download_results')
                    ->label('Download all results')
                    ->icon(IconEnums::DOWNLOAD->value)
                    ->requiresConfirmation()
                    ->modalDescription('This will download results for all completed predictions in this dataset. It may take a moment.')
                    ->action(function () {
                        $client = app(RemotePredictionClient::class);

                        try {
                            $client->ensureValidToken();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Token error: '.$e->getMessage())
                                ->send();

                            return;
                        }

                        $downloaded = 0;
                        $failed = 0;

                        $this->getOwnerRecord()
                            ->predictions()
                            ->where('remote_status', RemotePredictionStatus::COMPLETED->value)
                            ->whereNull('result_id')
                            ->limit(max(1, (int) config('prediction-workers.remote.worker.admin_bulk_limit', 20)))
                            ->get()
                            ->each(function (Prediction $prediction) use ($client, &$downloaded, &$failed): void {
                                try {
                                    $prediction->storeRemotePredictionResult($client);
                                    $downloaded++;
                                } catch (Throwable $e) {
                                    $prediction->forceFill([
                                        'remote_last_status_at' => now(),
                                        'remote_error_message' => $e->getMessage(),
                                    ])->save();
                                    $failed++;
                                }
                            });

                        Notification::make()
                            ->success()
                            ->title("Downloaded {$downloaded} result(s)".($failed > 0 ? ", {$failed} failed" : ''))
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Prediction $record) => PredictionResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
