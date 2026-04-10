<?php

namespace App\Filament\Resources\UploadQueues;

use App\Filament\Resources\Datasets\DatasetResource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\UploadQueues\Pages\ListUploadQueues;
use App\Filament\Resources\UploadQueues\Pages\CreateUploadQueue;
use App\Filament\Resources\UploadQueues\Pages\EditUploadQueue;
use App\Enums\IconEnums;
use App\Models\Dataset;
use App\Models\File;
use App\Models\UploadQueue;
use App\Rules\FileUniqueByHash;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadQueueResource extends Resource
{
    protected static ?string $model = UploadQueue::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::UPLOAD_QUEUE->value;
    protected static string | \UnitEnum | null $navigationGroup = 'Interactions management';
    protected static ?string $navigationLabel = 'Uploader';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextEntry::make('file.name')
                    ->formatStateUsing(fn (UploadQueue | null $record) => $record?->file->name)
                    ->hiddenOn('create')
                    ->state('File'),
                TextEntry::make('state')
                    ->formatStateUsing(fn (UploadQueue | null $record) => $record ? UploadQueue::enumState($record->state) : null)
                    ->state('State')
                    ->hiddenOn('create'),
                Select::make('type')
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
                Select::make('dataset_id')
                    ->label('Dataset')
                    ->relationship('dataset', 'name', fn (Builder $query, Get $get) => $query->where('type', $get('type'))->orderby('id', 'DESC'))
                    ->getOptionLabelFromRecordUsing(fn (Dataset $record) => "[$record->id]: $record->name (" . Str::limit($record->comment, 40) . ")")
                    ->disabled(fn (Get $get, UploadQueue | null $record) => !$get('type') || $record?->isRevertible())
                    ->preload()
                    ->createOptionForm(fn (Schema $schema) => DatasetResource::form($schema))
                    ->createOptionModalHeading('Add new dataset')
                    ->suffixActions([
                        Action::make('edit_dataset')
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
                Grid::make(2)
                    ->hidden(fn (Get $get) => $get('type') != UploadQueue::TYPE_PASSIVE_DATASET)
                    ->reactive()
                    ->schema([
                        Select::make('membrane_id')
                            ->reactive()
                            ->disabled()
                            ->label('Membrane')
                            ->hint('Membrane used in the dataset')
                            ->hintColor('danger')
                            ->relationship('dataset.membrane', 'name'),
                        Select::make('method_id')
                            ->reactive()
                            ->disabled()
                            ->label('Method')
                            ->hint('Method used in the dataset')
                            ->hintColor('danger')
                            ->relationship('dataset.method', 'name')
                    ]),
                FileUpload::make('path')
                    ->label('Interactions file')
                    ->required()
                    ->maxSize(1024 * 1024 * 20) // 20 MB
                    ->columnSpanFull()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) : string { 
                        session()->put('upload_meta', [
                            'hash' => md5_file($file->getRealPath()),
                            'mime' => $file->getMimeType()
                        ]);

                        return "[Dataset:" . $get('dataset_id') . "]-" . File::getUniqueNameForSave($file, 
                            UploadQueue::typeFolder($get('type') ? intval($get('type')) : null),
                            UploadQueue::disk()
                        );
                    })
                    ->hidden(fn (Get $get) => !$get('type') || $get('id'))
                    ->reactive()
                    ->rules([new FileUniqueByHash()])
                    ->preserveFilenames()
                    ->disk(UploadQueue::disk())
                    ->directory(fn (Get $get) => UploadQueue::typeFolder($get('type') ? intval($get('type')) : null))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('List of files uploaded manually by the contributors')
            ->columns([
                TextColumn::make('state')
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
                TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('ID'),
                TextColumn::make('file.name')
                    ->sortable()
                    ->limit(20)
                    ->tooltip(fn (UploadQueue $record) => $record->file?->name)
                    ->searchable()
                    ->label('File'),
                TextColumn::make('dataset.name')
                    ->sortable()
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn (UploadQueue $record) => $record->dataset?->name)
                    ->label('Dataset'),
                TextColumn::make('user.name')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->label('User'),
                TextColumn::make('type')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => UploadQueue::enumType($state)),
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
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('state')
                    ->label('State')
                    ->multiple()
                    ->query(fn (Builder $query, array $state) => $state['values'] ? $query->whereIn('state', $state['values']) : $query)
                    ->options(UploadQueue::enumState()),

                SelectFilter::make('type')
                    ->label('Type')
                    ->multiple()
                    ->query(fn (Builder $query, array $state) => $state['values'] ? $query->whereIn('type', $state['values']) : $query)
                    ->options(UploadQueue::enumType()),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->hidden(fn (UploadQueue $record) => !$record->isDeletable())
                    ->modalHeading('Delete upload queue record?')
                    ->modalDescription('This action will delete associated file and is irreversible.'),
                Action::make('revert')
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
                
                Action::make('config')
                    ->label(fn(UploadQueue $record) => $record->state == UploadQueue::STATE_CONFIGURED ? 'Reconfigure' : 'Configure')
                    ->color(fn(UploadQueue $record) => $record->state == UploadQueue::STATE_CONFIGURED ? 'warning' : 'success')
                    ->icon(IconEnums::SETTINGS->value)
                    ->modalContent(fn (UploadQueue $record) => view('livewire.upload-queue-configure-wrapper', [
                        'record' => $record
                    ]))
                    ->modalHeading('Configure upload process')
                    ->modalFooterActions([ // Hide footer buttons
                        Action::make('fake')
                            ->hidden()
                    ])
                    ->hidden(fn (UploadQueue $record) => !$record->isEditableConfig()),
                
                Action::make('start')
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
                
                Action::make('export')
                    ->label('Export')
                    ->icon(IconEnums::DOWNLOAD->value)
                    ->requiresConfirmation()
                    ->action(function (UploadQueue $record) {
                        return redirect()->route('export.upload-queue.raw', ['record' => $record->id]);
                    })
                    ->modalFooterActions([
                        Action::make('export_parsed')
                            ->label('As stored [db]')
                            ->color('warning')
                            ->disabled(fn (UploadQueue $record) => !$record->isFinished())
                            ->action(function (UploadQueue $record) {
                                return redirect()->route('export.upload-queue', ['record' => $record->id]);
                            }),
                        Action::make('export_raw')
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
            ->toolbarActions([
                BulkActionGroup::make([
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
            'index' => ListUploadQueues::route('/'),
            'create' => CreateUploadQueue::route('/create'),
            'edit' => EditUploadQueue::route('/{record}/edit'),
        ];
    }
}
