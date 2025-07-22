<?php

namespace App\Filament\Resources\UploadQueueResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\UploadQueueResource;
use App\Livewire\UploadQueueLogsTable;
use App\Models\UploadQueue;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUploadQueue extends EditRecord
{
    protected static string $resource = UploadQueueResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['membrane_id']);
        unset($data['method_id']);
        unset($data['path']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
                Actions\DeleteAction::make()
                    ->hidden(fn (UploadQueue $record) => !$record->isDeletable())
                    ->modalHeading('Delete upload queue record?')
                    ->modalDescription('This action will delete associated file and is irreversible.'),
                Actions\Action::make('revert')
                    ->label('Revert')
                    ->color('danger')
                    ->icon(IconEnums::RESTORE->value)
                    ->requiresConfirmation()
                    ->action(function (UploadQueue $record) {
                        return $record->revert();
                    })
                    ->modalDescription('This will remove all already added information for the whole dataset. If the dataset has multiple assigned files, all of their uploads will be reverted.')
                    ->modalHeading('Revert upload process?')
                    ->modalIcon(IconEnums::RESTORE->value)
                    ->hidden(fn (UploadQueue $record) => !$record->isRevertible())
                    ->tooltip('Revert to initial state'),
                // Actions\Action::make('cancel')
                //     ->label('Cancel')
                //     ->color('danger')
                //     ->icon(IconEnums::STOP->value)
                //     ->requiresConfirmation()
                //     ->action(function (UploadQueue $record) {
                //         return $record->cancel();
                //     })
                //     ->modalHeading('Stop uploading process?')
                //     ->modalDescription('This will stop the process of uploading and set the state to the canceled.')
                //     ->modalIcon(IconEnums::STOP->value)
                //     ->hidden(fn (UploadQueue $record) => !$record->isCancelable())
                //     ->tooltip('Cancel upload process'),
                
                Actions\Action::make('config')
                    ->label(fn(UploadQueue $record) => $record->state == UploadQueue::STATE_CONFIGURED ? 'Reconfigure' : 'Configure')
                    ->color(fn(UploadQueue $record) => $record->state == UploadQueue::STATE_CONFIGURED ? 'warning' : 'success')
                    ->icon(IconEnums::SETTINGS->value)
                    ->modalContent(fn (UploadQueue $record) => view('livewire.upload-queue-configure-wrapper', [
                        'record' => $record
                    ]))
                    ->modalHeading('Configure upload process')
                    ->modalFooterActions([ // Hide footer buttons
                        Actions\Action::make('fake')
                            ->hidden()
                    ])
                    ->hidden(fn (UploadQueue $record) => !$record->isEditableConfig()),
                
                Actions\Action::make('start')
                    ->label('Start')
                    ->color('success')
                    ->icon(IconEnums::CHECK->value)
                    ->requiresConfirmation()
                    ->modalHeading('Do you want to start the upload process?')
                    ->modalDescription('This will add the file to the queue to be processed.')
                    ->action(function (UploadQueue $record) {
                        $record->start();
                    })
                    ->hidden(fn (UploadQueue $record) => !$record->isReadyToStart()),
                
                Actions\Action::make('export')
                    ->label('Export')
                    ->icon(IconEnums::DOWNLOAD->value)
                    ->requiresConfirmation()
                    ->action(function (UploadQueue $record) {
                        return redirect()->route('export.upload-queue.raw', ['record' => $record->id]);
                    })
                    ->modalFooterActions([
                        Actions\Action::make('export_parsed')
                            ->label('As stored [db]')
                            ->color('warning')
                            ->disabled(fn (UploadQueue $record) => !$record->isFinished())
                            ->action(function (UploadQueue $record) {
                                return redirect()->route('export.upload-queue', ['record' => $record->id]);
                            }),
                        Actions\Action::make('export_raw')
                            ->label('As uploaded [raw]')
                            ->action(function (UploadQueue $record) {
                                return redirect()->route('export.upload-queue.raw', ['record' => $record->id]);
                            }),
                    ])
                    ->modalDescription('How would you like to export this dataset?')
                    ->modalHeading('Export dataset')
                    ->modalIcon(IconEnums::DOWNLOAD->value)
                    ->tooltip('Export data'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            UploadQueueLogsTable::class
        ];
    }

    public function getFooterWidgetsColumns(): int | string | array
    {
        return 1;
    }
}
