<?php

namespace App\Filament\Resources\SharedRelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Enums\IconEnums;
use App\Models\File;
use App\Models\FileRestrictionType;
use App\Models\Membrane;
use App\Models\Method;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileRelationManager extends RelationManager
{
    protected static string $relationship = 'files';
    protected static string | \BackedEnum | null $icon = IconEnums::FILES->value;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->query(null)
            ->emptyStateHeading('No assigned files')
            ->emptyStateDescription($this->getEmptyStateDescription())
            ->emptyStateIcon(IconEnums::MEMBRANE->value)
            ->columns([
                TextColumn::make('name')
                    ->sortable(),
                IconColumn::make('type')
                    ->alignCenter()
                    ->icon(fn (File $record) => match ($record->type) {
                        File::TYPE_EXPORT_INTERACTIONS_ACTIVE_PUBLICATION => IconEnums::DATASET->value,
                        File::TYPE_EXPORT_INTERACTIONS_PASSIVE_PUBLICATION => IconEnums::DATASET->value,
                        File::TYPE_EXPORT_INTERACTIONS_MEMBRANE => IconEnums::DATASET->value,
                        File::TYPE_EXPORT_INTERACTIONS_METHOD => IconEnums::DATASET->value,
                        File::TYPE_IMAGE => IconEnums::FILE_IMAGE->value,
                        default => IconEnums::FILE_DOCUMENT->value
                    })
                    ->tooltip(fn (File $record) => File::enumType($record->type)),
                ImageColumn::make('user_id')
                    ->label('Uploaded by')
                    ->alignCenter()
                    ->getStateUsing(fn (File $record) => $record->user?->getFilamentAvatarUrl())
                    ->circular()
                    ->size(35)
                    ->tooltip(function (File $record) {
                        return $record->user?->name;
                    }),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('addNewFile')
                    ->label('Add new file')
                    ->icon(IconEnums::ADD->value)
                    ->schema([
                        Select::make('type')
                            ->label('File type')
                            ->required()
                            ->reactive()
                            ->options(fn() => match($this->getOwnerRecord()::class) {
                                Method::class => File::enumTypes(FileRestrictionType::METHOD),
                                Membrane::class => File::enumTypes(FileRestrictionType::MEMBRANE),
                                default => File::enumTypes()
                            }),
                        TextInput::make('name')
                            ->label('Alternative name')
                            ->hint(fn(Get $get) => $get('type') == File::TYPE_COSMO_MEMBRANE ? 'Not available for structure files' : 'If set, will be used instead of file name when downloaded. Max 30 characters.')
                            ->rule('regex:/^[a-zA-Z0-9-_]+$/') 
                            ->disabled(fn (Get $get) => $get('type') == File::TYPE_COSMO_MEMBRANE)
                            ->maxLength(30),
                        FileUpload::make('path')
                            ->label('File')
                            ->required()
                            ->reactive()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (Get $get, TemporaryUploadedFile $file) {
                                if($get('type') == File::TYPE_COSMO_MEMBRANE)
                                {
                                    return $this->getOwnerRecord()->abbreviation . '_cosmo.inp';
                                }
                                return $file->getFilename();
                            })
                            ->directory(fn (Get $get) => $this->getOwnerRecord()->folder() . $this->ownerRecord->id . '/' . File::getEnumTypeFolder($get('type')))
                            ->disk('public')
                            ->hidden(fn (Get $get) => !$get('type'))
                            ->hint(fn (Get $get) => !$get('type') ? 'Please, select file type' : null)
                            ->hintColor(fn (Get $get) => !$get('type') ? 'danger' : null)
                    ])
                    ->action(function (array $data) {
                        $file = File::create([
                            'name' => isset($data['name']) ? $data['name'] : null,
                            'type' => $data['type'],
                            'path' => $data['path'],
                            'storage' => 'public',
                            'hash' => null
                        ]);
                        
                        $this->ownerRecord->files()->attach($file->id, [
                            'model_type' => $this->getOwnerRecord()::class
                        ]);
                    })
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->color('success')
                    ->icon(IconEnums::DOWNLOAD->value)
                    ->disabled(fn (File $record) => !$record->hash)
                    ->openUrlInNewTab()
                    ->url(fn (File $record) => $record->hash ? route('public.download', ['hash' => $record->hash]) : null),
                DeleteAction::make()
                    ->successNotificationTitle('File deleted'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return match($ownerRecord::class) {
            // Method::class => 'Gallery',
            default => parent::getTitle($ownerRecord, $pageClass)
        };
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string
    {
        return match($ownerRecord::class) {
            // Method::class => IconEnums::FILE_IMAGE->value,
            default => self::$icon
        };
    }

    private function getEmptyStateDescription()
    {
        return match ($this->ownerRecord::class) {
            // Method::class => 'Start by adding new image file.',
            // Membrane::class => 'Start by adding new image or COSMO structure file.',
            default => 'Start by adding new file.'
        };
    }
}
