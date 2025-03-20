<?php

namespace App\Filament\Resources\StructureResource\Pages;

use App\Filament\Resources\StructureResource;
use App\Libraries\Identifiers;
use App\Libraries\Rdkit;
use App\Models\Identifier;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Auth;
use Throwable;

use function Filament\Support\is_app_url;

class CreateStructure extends CreateRecord
{
    protected static string $resource = StructureResource::class;
    public $isValidated = false;

    /**
     * Add user id to data
     */
    protected function mutateFormDataBeforeCreate(array $formData): array
    {
        $data = $formData;
        $data['user_id'] = Auth::user()->id;

        // Remove identifiers
        unset($data['smiles']);
        unset($data['inchikey']);

        return $data;
    }

    /**
     * After create, save identifiers
     */
    protected function afterCreate(array $data): void
    {
        $substance = $this->getRecord();
        // Add MMDB identifier
        $substance->update([
            'identifier' => Identifiers::get_identifier($substance->id)
        ]);

        // Save other provided identifiers
        $substance->identifiers()->create([
            'value' => $data['smiles'],
            'type'  => Identifier::TYPE_SMILES,
            'user_id' => Auth::user()->id,
            'is_active' => true,
            'state' => Identifier::STATE_VALIDATED
        ]);

        $substance->identifiers()->create([
            'value' => $data['inchikey'],
            'type'  => Identifier::TYPE_INCHIKEY,
            'user_id' => Auth::user()->id,
            'is_active' => true,
            'state' => Identifier::STATE_VALIDATED,
            'server' => Identifier::SERVER_RDKIT
        ]);
    }


    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->databaseTransaction()
                ->formId('form')
                ->label(fn () => $this->isValidated ? 'Save' : 'Validate')
                ->color(fn () => $this->isValidated ? 'success' : 'warning')
                ->action(function() {
                    if($this->isValidated)
                        $this->create();
                    $this->validateSmiles();
                } )
        ];
    }

    public function validateSmiles() {
        $states = $this->form->getState();
        $state = $states['smiles'];

        if(!$state) return;

        // Connect RdKit
        $rdkit = new Rdkit();
        if(!$rdkit::is_connected())
        {
            Notification::make()
                ->danger()
                ->title('Rdkit disconnected')
                ->body('Cannot establish connection to RdKit server. Please, try again.')
                ->send();
            return; 
        }
        
        // Validate SMILES
        $canonized = $rdkit->canonize_smiles($state);
        if(!$canonized)
        {
            Notification::make()
                ->danger()
                ->title('Invalid SMILES')
                ->body('SMILES cannot be canonized. Please, check your input and try again.')
                ->send();
            return; 
        }

        // Get general info
        $info = $rdkit->get_general_info($canonized);
        if(!$info)
        {
            Notification::make()
                ->danger()
                ->title('Unable to obtain molecule info.')
                ->body('Invalid response from the remote server. Please, try again.')
                ->send();
            return; 
        }

        // Set values
        $this->form->fill([
            ...$states,
            'molecular_weight' => round($info->MW, 2),
            'logp' => round($info->LogP, 2),
            'inchikey' => $info->inchi
        ]);

        $this->isValidated = true;
    }

    /**
     * Custom create function
     */
    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $substance_data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($substance_data);

            $this->form->model($this->getRecord())->saveRelationships();

            $this->afterCreate($data);
            // $this->callHook('afterCreate');

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->rememberData();

        $this->getCreatedNotification()?->send();

        if ($another) {
            // Ensure that the form record is anonymized so that relationships aren't loaded.
            $this->form->model($this->getRecord()::class);
            $this->record = null;

            $this->fillForm();

            return;
        }

        $redirectUrl = $this->getRedirectUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
    }
}
