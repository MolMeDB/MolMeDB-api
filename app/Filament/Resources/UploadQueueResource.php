<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\UploadQueueResource\Pages;
use App\Models\Dataset;
use App\Models\File;
use App\Models\UploadQueue;
use App\Rules\FileUniqueByHash;
use Filament\Forms\Form;
use Filament\Forms\Components;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadQueueResource extends Resource
{
    protected static ?string $model = UploadQueue::class;
    protected static ?string $navigationIcon = IconEnums::UPLOAD_QUEUE->value;
    protected static ?string $navigationGroup = 'Interactions management';
    protected static ?string $navigationLabel = 'Uploader';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Components\Placeholder::make('file.name')
                    ->content(fn (UploadQueue | null $record) => $record?->file->name)
                    ->hiddenOn('create')
                    ->label('File'),
                Components\Placeholder::make('state')
                    ->content(fn (UploadQueue | null $record) => $record ? UploadQueue::enumState($record->state) : null)
                    ->label('State')
                    ->hiddenOn('create'),
                Components\Select::make('type')
                    ->label('Type')
                    ->options(fn() => UploadQueue::enumType())
                    ->disabledOn('edit')
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, ?int $state) {
                        $set('dataset_id', null);
                        $set('membrane_id', null);
                        $set('method_id', null);
                    })
                    ->required(),
                Components\Select::make('dataset_id')
                    ->label('Dataset')
                    ->relationship('dataset', 'name', fn (Builder $query, Get $get) => $query->where('type', $get('type'))->orderby('id', 'DESC'))
                    ->getOptionLabelFromRecordUsing(fn (Dataset $record) => "[$record->id]: $record->name (" . Str::limit($record->comment, 40) . ")")
                    ->disabled(fn (Get $get, UploadQueue | null $record) => !$get('type') || $record?->isRevertible())
                    ->preload()
                    ->createOptionForm(fn (Form $form) => DatasetResource::form($form))
                    ->createOptionModalHeading('Add new dataset')
                    ->suffixActions([
                        Components\Actions\Action::make('edit_dataset')
                            ->icon(IconEnums::VIEW->value)
                            ->url(fn (Get $get) => $get('dataset_id') ? DatasetResource::getUrl('edit', ['record' => Dataset::withTrashed()->find($get('dataset_id'))]) : null)
                            ->tooltip('View dataset')
                            ->openUrlInNewTab(),
                    ])
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, ?int $state) {
                        if ($state) {
                            $dataset = Dataset::withTrashed()->find($state);
                            $set('membrane_id', $dataset->membrane_id);
                            $set('method_id', $dataset->method_id);
                        }
                    })
                    ->afterStateHydrated(function (callable $set, ?int $state) {
                        if ($state) {
                            $dataset = Dataset::withTrashed()->find($state);
                            $set('membrane_id', $dataset->membrane_id);
                            $set('method_id', $dataset->method_id);
                        }
                    })
                    ->required(),
                Components\Grid::make(2)
                    ->hidden(fn (Get $get) => $get('type') != UploadQueue::TYPE_PASSIVE_DATASET)
                    ->reactive()
                    ->schema([
                        Components\Select::make('membrane_id')
                            ->reactive()
                            ->disabled()
                            ->label('Membrane')
                            ->hint('Membrane used in the dataset')
                            ->hintColor('danger')
                            ->relationship('dataset.membrane', 'name'),
                        Components\Select::make('method_id')
                            ->reactive()
                            ->disabled()
                            ->label('Method')
                            ->hint('Method used in the dataset')
                            ->hintColor('danger')
                            ->relationship('dataset.method', 'name')
                    ]),
                Components\FileUpload::make('path')
                    ->label('Interactions file')
                    ->required()
                    ->maxSize(1024 * 1024 * 20) // 20 MB
                    ->columnSpanFull()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) : string { 
                        return File::getUniqueNameForSave($file, 
                            UploadQueue::typeFolder($get('type') ? intval($get('type')) : null),
                            UploadQueue::DISK
                        );
                    })
                    ->hidden(fn (Get $get) => !$get('type') || $get('id'))
                    ->reactive()
                    ->rules([new FileUniqueByHash()])
                    ->preserveFilenames()
                    ->disk(UploadQueue::DISK)
                    ->directory(fn (Get $get) => UploadQueue::typeFolder($get('type') ? intval($get('type')) : null))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('List of files uploaded manually by the contributors')
            ->columns([
                Tables\Columns\TextColumn::make('state')
                    ->sortable()
                    ->alignCenter()
                    ->label('State')
                    ->colors([
                        'primary',
                        'warning' => static fn ($state): bool => in_array($state, [
                            UploadQueue::STATE_RUNNING,
                        ]),
                        'success' => static fn ($state): bool => in_array($state, [
                            UploadQueue::STATE_DONE
                        ]),
                        'danger' => static fn ($state): bool => in_array($state, [
                            UploadQueue::STATE_ERROR,
                            UploadQueue::STATE_CANCELED
                        ]),
                    ])
                    ->formatStateUsing(fn ($state) => UploadQueue::enumState($state)),
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('ID'),
                Tables\Columns\TextColumn::make('file.name')
                    ->sortable()
                    ->limit(20)
                    ->tooltip(fn (UploadQueue $record) => $record->file?->name)
                    ->searchable()
                    ->label('File'),
                Tables\Columns\TextColumn::make('dataset.name')
                    ->sortable()
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn (UploadQueue $record) => $record->dataset?->name)
                    ->label('Dataset'),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->label('User'),
                Tables\Columns\TextColumn::make('type')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => UploadQueue::enumType($state)),
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
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->label('State')
                    ->multiple()
                    ->query(fn (Builder $query, array $state) => $state['values'] ? $query->whereIn('state', $state['values']) : $query)
                    ->options(UploadQueue::enumState()),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->multiple()
                    ->query(fn (Builder $query, array $state) => $state['values'] ? $query->whereIn('type', $state['values']) : $query)
                    ->options(UploadQueue::enumType()),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (UploadQueue $record) => !$record->isDeletable())
                    ->modalHeading('Delete upload queue record?')
                    ->modalDescription('This action will delete associated file and is irreversible.'),
                Tables\Actions\Action::make('revert')
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
                // Tables\Actions\Action::make('cancel')
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
                
                Tables\Actions\Action::make('config')
                    ->label(fn(UploadQueue $record) => $record->state == UploadQueue::STATE_CONFIGURED ? 'Reconfigure' : 'Configure')
                    ->color(fn(UploadQueue $record) => $record->state == UploadQueue::STATE_CONFIGURED ? 'warning' : 'success')
                    ->icon(IconEnums::SETTINGS->value)
                    ->modalContent(fn (UploadQueue $record) => view('livewire.upload-queue-configure-wrapper', [
                        'record' => $record
                    ]))
                    ->modalHeading('Configure upload process')
                    ->modalFooterActions([ // Hide footer buttons
                        Tables\Actions\Action::make('fake')
                            ->hidden()
                    ])
                    ->hidden(fn (UploadQueue $record) => !$record->isEditableConfig()),
                
                Tables\Actions\Action::make('start')
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
                
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->icon(IconEnums::DOWNLOAD->value)
                    ->requiresConfirmation()
                    ->action(function (UploadQueue $record) {
                        return redirect()->route('export.upload-queue.raw', ['record' => $record->id]);
                    })
                    ->modalFooterActions([
                        Tables\Actions\Action::make('export_parsed')
                            ->label('As stored [db]')
                            ->color('warning')
                            ->disabled(fn (UploadQueue $record) => !$record->isFinished())
                            ->action(function (UploadQueue $record) {
                                return redirect()->route('export.upload-queue', ['record' => $record->id]);
                            }),
                        Tables\Actions\Action::make('export_raw')
                            ->label('As uploaded [raw]')
                            ->action(function (UploadQueue $record) {
                                return redirect()->route('export.upload-queue.raw', ['record' => $record->id]);
                            }),
                    ])
                    ->modalDescription('How would you like to export this dataset?')
                    ->modalHeading('Export dataset')
                    ->modalIcon(IconEnums::DOWNLOAD->value)
                    ->tooltip('Export data'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUploadQueues::route('/'),
            'create' => Pages\CreateUploadQueue::route('/create'),
            'edit' => Pages\EditUploadQueue::route('/{record}/edit'),
        ];
    }
}
