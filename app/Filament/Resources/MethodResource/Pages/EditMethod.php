<?php

namespace App\Filament\Resources\MethodResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\MethodResource;
use App\ValueObjects\MethodParameters;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use phpDocumentor\Reflection\DocBlock\Tags\MethodParameter;

class EditMethod extends EditRecord
{
    protected static string $resource = MethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()  
                ->modalHeading('Delete method?')
                ->modalDescription('Deleting method will delete all associated files, datasets and interactions.')
                ->modalSubmitActionLabel('Understand. Delete'),
            Actions\ForceDeleteAction::make()  
                ->modalHeading('Delete method?')
                ->modalDescription('Force deleting method will delete all associated files, datasets and interactions. This step is irreversible.')
                ->modalSubmitActionLabel('Understand. Delete all.'),
            Actions\RestoreAction::make()
                ->icon(IconEnums::RESTORE->value)
                ->modalHeading('Restore method?')
                ->modalDescription('Warning! All associated files, datasets and interactions will be also restored and be directly visible.')
                ->modalSubmitActionLabel('Understand. Restore')
        ];
    }

    public function getTitle(): string
    {
        return ($this->record->trashed() ? '(DELETED) ' : "") . 
            "Edit method [ID:" . $this->record->id . "] - " . $this->record->name;
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Details';
    }

    public function getContentTabIcon(): ?string
    {
        return IconEnums::METHOD->value;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['parameters'] = new MethodParameters($data['parameters']);

        return $data;
    }
}
