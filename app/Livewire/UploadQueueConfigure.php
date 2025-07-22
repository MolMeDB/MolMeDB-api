<?php

namespace App\Livewire;

use App\Filament\Resources\UploadQueueResource;
use App\Models\UploadQueue;
use App\Rules\UploadFile;
use App\Rules\UploadFile\Identifiers\ColumnSmiles;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class UploadQueueConfigure extends Component
{
    public UploadQueue $record;
    public array $previewRows = [];
    public array $columnMapping = [];
    public array $columnValidTypes = [];
    public int $skipFirstRow = 0;
    public int $totalRows = 0;
    public int $startLine = 0;
    public string $separator = ',';

    public bool $isValidated = False;
    public bool $isValidating = False;

    public array $validColumnTypes = [];

    public array $errorMessages = [];
    public array $warningMessages = [];
    public bool $skipWarnings = False;

    public array $validSeparators = [
        ';',
        ',',
        '\t'
    ];

    const IGNORE_COLUMN = 'ignore';

    private array $columnTypeOptions = [
        'passive' => [
            UploadFile\Identifiers\ColumnName::class,
            UploadFile\Identifiers\ColumnSmiles::class,
            UploadFile\Identifiers\ColumnPubchem::class,
            UploadFile\Identifiers\ColumnPdb::class,
            UploadFile\Identifiers\ColumnChembl::class,
            UploadFile\Identifiers\ColumnChebi::class,
            UploadFile\Identifiers\ColumnDrugbank::class,
            UploadFile\ColumnComment::class,
            UploadFile\ColumnPrimaryReference::class,
            UploadFile\ColumnSecondaryReference::class,
            UploadFile\ColumnLogP::class,
            UploadFile\PassiveInteractions\ColumnXmin::class,
            UploadFile\PassiveInteractions\ColumnXminAcc::class,
            UploadFile\PassiveInteractions\ColumnGpen::class,
            UploadFile\PassiveInteractions\ColumnGpenAcc::class,
            UploadFile\PassiveInteractions\ColumnGwat::class,
            UploadFile\PassiveInteractions\ColumnGwatAcc::class,
            UploadFile\PassiveInteractions\ColumnLogK::class,
            UploadFile\PassiveInteractions\ColumnLogKAcc::class,
            UploadFile\PassiveInteractions\ColumnLogPerm::class,
            UploadFile\PassiveInteractions\ColumnLogPermAcc::class,
        ],
        'active' => [
            UploadFile\Identifiers\ColumnName::class,
            UploadFile\Identifiers\ColumnSmiles::class,
            UploadFile\Identifiers\ColumnUniprot::class,
            UploadFile\Identifiers\ColumnPubchem::class,
            UploadFile\Identifiers\ColumnPdb::class,
            UploadFile\Identifiers\ColumnChembl::class,
            UploadFile\Identifiers\ColumnChebi::class,
            UploadFile\Identifiers\ColumnDrugbank::class,
            UploadFile\ColumnComment::class,
            UploadFile\ColumnPrimaryReference::class,
            UploadFile\ColumnSecondaryReference::class,
            UploadFile\ColumnLogP::class,
            UploadFile\ActiveInteractions\ColumnTarget::class,
            UploadFile\ActiveInteractions\ColumnEc50::class,
            UploadFile\ActiveInteractions\ColumnEc50Acc::class,
            UploadFile\ActiveInteractions\ColumnIc50::class,
            UploadFile\ActiveInteractions\ColumnIc50Acc::class,
            UploadFile\ActiveInteractions\ColumnKi::class,
            UploadFile\ActiveInteractions\ColumnKiAcc::class,
            UploadFile\ActiveInteractions\ColumnKm::class,
            UploadFile\ActiveInteractions\ColumnKmAcc::class,
        ]
    ];

    public function mount(UploadQueue $record)
    {
        $this->record = $record;

        $variant = match($this->record->type)
        {
            UploadQueue::TYPE_PASSIVE_DATASET => 'passive',
            UploadQueue::TYPE_ACTIVE_DATASET => 'active',
            default => null
        };
        
        $this->validColumnTypes = $this->columnTypeOptions[$variant] ?? [];

        $this->validColumnTypes = [
            self::IGNORE_COLUMN => 'Ignore',
            ...(
                array_combine(
                    array_map(
                        fn($column) => $column::$key, 
                        $this->validColumnTypes,
                    ),
                    array_map(
                        fn($column) => $column::$label, 
                        $this->validColumnTypes,
                    ))
                )
        ];

        if($this->record->config)
        {
            $this->skipFirstRow = isset($this->record->config['skip_first_row']) ? $this->record->config['skip_first_row'] : $this->skipFirstRow;
            $this->separator = isset($this->record->config['separator']) && in_array($this->record->config['separator'], $this->validSeparators) ? $this->record->config['separator'] : $this->separator;
            $this->columnMapping = isset($this->record->config['attributes']) ? array_filter(
                $this->record->config['attributes'],
                fn($val) => array_key_exists($val, $this->validColumnTypes)
            )  : $this->columnMapping;
        }

        $this->reloadTableContent();

        return $this;
    }

    private function reloadTableContent($updateColumnOptions = true)
    {
        $path = $this->record->file?->path;
        if ($this->record->file?->existsOnDisk(UploadQueue::DISK)) 
        {
            $stream = Storage::disk(UploadQueue::DISK)->readStream($path);

            if (!$stream) {
                $this->errorMessages[] = 'Cannot read uploaded file. Please, try again later.';
                $this->isValidated = false;
                return; 
            }

            $i = 0;
            $this->previewRows = [];
            while(($line = fgets($stream)) !== false) {
                $i++;
                if(($this->skipFirstRow && $i == 1) || 
                    $i < $this->startLine)
                {
                    continue;
                }

                $line = mb_convert_encoding($line, 'UTF-8', 'auto');

                if(count($this->previewRows) < 6)
                {
                    $this->previewRows[] = str_getcsv($line, $this->separator);
                }
            }

            $this->totalRows = $i;
            fclose($stream);

            if(count($this->previewRows) && $updateColumnOptions)
            {
                $total_columns = count($this->previewRows[0] ?? []);
                $this->columnMapping = count($this->columnMapping) == $total_columns ? $this->columnMapping : array_fill(0, $total_columns, null);
                $this->updateColumnTypeOptions();
            }
        }
        else
        {
            $this->errorMessages[] = 'Uploaded file not found on remote server.';
            return;
        }
    }

    private function updateColumnTypeOptions() 
    {
        $used = $this->columnMapping;

        $t = array_fill(0, count($used), $this->validColumnTypes);

        $this->columnValidTypes = array_map(
            fn ($types, $key) => array_filter($types, function($value) use ($used, $key) {
                return !in_array($value, $used) || $value === $used[$key] || $value === self::IGNORE_COLUMN;
            }, ARRAY_FILTER_USE_KEY),
            $t, 
            array_keys($t)
        );
    }

    public function updatedSkipFirstRow()
    {
        $this->isValidated = False;
        $this->reloadTableContent(updateColumnOptions: false);
    }

    public function updatedSeparator()
    {
        $this->isValidated = False;
        $this->reloadTableContent();
    }

    public function updatedColumnMapping()
    {
        $this->isValidated = False;
        $this->updateColumnTypeOptions();
    }

    public function render()
    {
        return view('livewire.upload-queue-configure');
    }

    public function validateColumns()
    { 
        $this->errorMessages = [];

        $columnMapping = $this->columnMapping;

        if(empty($columnMapping) || !is_array($columnMapping) || !in_array(UploadFile\Identifiers\ColumnSmiles::$key, $columnMapping))
        {
            $this->errorMessages[] = "Column " . UploadFile\Identifiers\ColumnSmiles::$label . " is required.";
            $this->isValidated = false;
            return;
        }

        if($this->record->type == UploadQueue::TYPE_ACTIVE_DATASET && 
            !in_array(UploadFile\ActiveInteractions\ColumnTarget::$key, $columnMapping))
        {
            $this->errorMessages[] = 'Column target is required.';
            $this->isValidated = false;
            return;
        }

        if($this->record->type == UploadQueue::TYPE_ACTIVE_DATASET && 
            !in_array(UploadFile\ActiveInteractions\ColumnEc50::$key, $columnMapping) && 
            !in_array(UploadFile\ActiveInteractions\ColumnIc50::$key, $columnMapping) && 
            !in_array(UploadFile\ActiveInteractions\ColumnKi::$key, $columnMapping) && 
            !in_array(UploadFile\ActiveInteractions\ColumnKm::$key, $columnMapping))
        {
            $this->errorMessages[] = 'At least one active interaction column is required.';
            $this->isValidated = false;
            return;
        }

        if($this->record->type == UploadQueue::TYPE_PASSIVE_DATASET && 
            !in_array(UploadFile\PassiveInteractions\ColumnGpen::$key, $columnMapping) && 
            !in_array(UploadFile\PassiveInteractions\ColumnGwat::$key, $columnMapping) && 
            !in_array(UploadFile\PassiveInteractions\ColumnLogK::$key, $columnMapping) && 
            !in_array(UploadFile\PassiveInteractions\ColumnLogPerm::$key, $columnMapping) && 
            !in_array(UploadFile\PassiveInteractions\ColumnXmin::$key, $columnMapping))
        {
            $this->errorMessages[] = 'At least one passive interaction column is required.';
            $this->isValidated = false;
            return;
        }

        $path = $this->record->file?->path;
        if ($this->record->file?->existsOnDisk(UploadQueue::DISK)) 
        {
            $stream = Storage::disk(UploadQueue::DISK)->readStream($path);

            if (!$stream) {
                $this->errorMessages[] = 'Cannot read uploaded file. Please, try again later.';
                $this->isValidated = False;
                return;
            }

            $i = 0;
            while(($line = fgets($stream)) !== false) {
                $line = mb_convert_encoding($line, 'UTF-8', 'auto');
                $i++;
                if($this->skipFirstRow && $i == 1)
                {
                    continue;
                }

                $row = array_combine($this->columnMapping, str_getcsv($line, $this->separator));

                $validator = $this->defineValidator($row);

                if($validator->fails())
                {
                    $this->errorMessages += array_map(fn($msg) => "Line $i: $msg", $validator->errors()->all());
                    $this->isValidated = false;
                    $this->startLine = $i-2 >= 0 ? $i-2 : 0;
                    $this->reloadTableContent(updateColumnOptions: false);
                    return;
                }

                // Check if some warnings should be shown
                $validatorClasses = match($this->record->type) 
                {
                    UploadQueue::TYPE_ACTIVE_DATASET => $this->columnTypeOptions['active'],
                    UploadQueue::TYPE_PASSIVE_DATASET => $this->columnTypeOptions['passive'],
                    default => []
                };

                foreach($validatorClasses as $validatorClass)
                {
                    if(!isset($row[$validatorClass::$key]) || isset($this->warningMessages[$validatorClass::$key])) continue;

                    if(method_exists($validatorClass, 'isOutOfLimits') && 
                        (new $validatorClass())->isOutOfLimits($row[$validatorClass::$key], $this->record->dataset->method))
                    {
                        $this->warningMessages[$validatorClass::$key] = 'Some values for column ' . $validatorClass::$label . ' are out of method\'s limits.';
                    }
                }
            }

            fclose($stream);
            
            $this->isValidated = True;
        }
        else
        {
            $this->errorMessages[] = 'Uploaded file not found on remote server.';
            $this->isValidated = false;
        }
    }


    public function defineValidator(array $data) : \Illuminate\Validation\Validator
    {
        $validatorClasses = match($this->record->type) 
        {
            UploadQueue::TYPE_ACTIVE_DATASET => $this->columnTypeOptions['active'],
            UploadQueue::TYPE_PASSIVE_DATASET => $this->columnTypeOptions['passive'],
            default => null
        };

        if(!$validatorClasses) {
            throw new Exception('Validators not implemented for type ' . $this->record->enumType($this->record->type));
        }

        return Validator::make($data, array_combine(
            array_map(fn($validatorClass) => $validatorClass::$key, $validatorClasses),
            array_map(fn($validatorClass) => new $validatorClass(), $validatorClasses)
        ));
    }

    public function save()
    {
        $settings = [
            'skip_first_row' => $this->skipFirstRow,
            'separator' => $this->separator,
            'attributes' => $this->columnMapping
        ];

        $this->record->config = $settings;
        $this->record->state = UploadQueue::STATE_CONFIGURED;
        $this->record->save();

        Notification::make()
            ->title('Settings saved')
            ->body('Please, close the config form.')
            ->success()
            ->send();
    }
}