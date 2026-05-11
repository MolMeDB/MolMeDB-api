<?php

namespace App\Models;

use App\Casts\UploadQueueConfigCasts;
use App\Casts\UploadQueueLogCasts;
use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Jobs\ProcessUploadQueueRecord;
use App\ValueObjects\UploadQueueLog;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Modules\Rdkit\Rdkit;

class UploadQueue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'upload_queue';

    protected static $disk = null;

    protected function casts(): array
    {
        return [
            'config' => UploadQueueConfigCasts::class,
            'logs' => UploadQueueLogCasts::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    const TYPE_PASSIVE_DATASET = Dataset::TYPE_PASSIVE;

    const TYPE_ACTIVE_DATASET = Dataset::TYPE_ACTIVE;
    // const TYPE_ENERGY = 3;

    private static $enum_types =
        [
            self::TYPE_PASSIVE_DATASET => 'Passive interactions',
            self::TYPE_ACTIVE_DATASET => 'Active interactions',
            // self::TYPE_ENERGY => 'Energy',
        ];

    public static function enumType($type = null)
    {
        if ($type === null) {
            return self::$enum_types;
        }

        if (array_key_exists($type, self::$enum_types)) {
            return self::$enum_types[$type];
        }

        return null;
    }

    public static function disk(): ?string
    {
        if (! self::$disk) {
            self::$disk = Filesystem::where('type', Filesystem::TYPE_UPLOAD_STORAGE)->first()?->systemName;
        }

        return self::$disk;
    }

    public static function typeFolder($type): ?string
    {
        return match ($type) {
            self::TYPE_PASSIVE_DATASET => 'upload_queue/passive',
            self::TYPE_ACTIVE_DATASET => 'upload_queue/active',
            default => null,
        };
    }

    /** STATES */
    const STATE_UPLOADED = -2;

    const STATE_CONFIGURED = -1;

    const STATE_REVIEW_REQUIRED = 50;

    const STATE_PENDING = 0;

    const STATE_RUNNING = 1;

    const STATE_DONE = 2;

    const STATE_ERROR = 3;

    const STATE_CANCELED = 4;

    /**
     * Enum states
     */
    public static $enum_states =
        [
            self::STATE_UPLOADED => 'Uploaded',
            self::STATE_CONFIGURED => 'Configured',
            self::STATE_REVIEW_REQUIRED => 'Review required',
            self::STATE_PENDING => 'Pending',
            self::STATE_RUNNING => 'Running',
            self::STATE_DONE => 'Done',
            self::STATE_ERROR => 'Error',
            self::STATE_CANCELED => 'Canceled',
        ];

    public static $ui_enum_states =
        [
            self::STATE_UPLOADED => 'Configuration required',
            self::STATE_CONFIGURED => 'Ready to start upload',
            self::STATE_REVIEW_REQUIRED => 'Waiting for validation',
            self::STATE_PENDING => 'Pending upload',
            self::STATE_RUNNING => 'Uploading',
            self::STATE_DONE => 'Uploaded',
            self::STATE_ERROR => 'Validation error',
            self::STATE_CANCELED => 'Canceled',
        ];

    public static function enumState($state = null)
    {
        if ($state === null) {
            return self::$enum_states;
        }

        if (array_key_exists($state, self::$enum_states)) {
            return self::$enum_states[$state];
        }

        return null;
    }

    public static function canBeAddedNewRecords(): bool
    {
        $rdkit = new Rdkit;

        return $rdkit->is_connected();
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function isRevertible(): bool
    {
        return $this->state !== self::STATE_UPLOADED &&
            $this->state !== self::STATE_CONFIGURED;
    }

    public function isDeletable(): bool
    {
        return $this->state === self::STATE_UPLOADED ||
            $this->state == self::STATE_CONFIGURED ||
            $this->state === self::STATE_REVIEW_REQUIRED;
    }

    public function isFinished(): bool
    {
        return $this->state === self::STATE_DONE;
    }

    public function isCancelable(): bool
    {
        return $this->state === self::STATE_PENDING ||
            $this->state === self::STATE_RUNNING ||
            $this->state === self::STATE_REVIEW_REQUIRED;
    }

    public function isEditableConfig(): bool
    {
        return $this->state === self::STATE_UPLOADED ||
            $this->state === self::STATE_CONFIGURED;
    }

    public function isReadyToStart(): bool
    {
        return $this->state === self::STATE_CONFIGURED &&
            $this->hasValidConfig();
    }

    public function canBeReuploaded(): bool
    {
        return $this->state === self::STATE_ERROR;
    }

    public function canBeConfigured(): bool
    {
        return in_array(
            $this->state,
            [
                self::STATE_UPLOADED,
                self::STATE_ERROR,
                self::STATE_CONFIGURED,
            ],
            true
        );
    }

    public function canBeEnqueued(): bool
    {
        return $this->state === self::STATE_CONFIGURED &&
            $this->hasValidConfig() &&
            $this->config->quickValidationPassed();
    }

    public function canBeRevertedToConfigState(): bool
    {
        return $this->state === self::STATE_PENDING ||
            $this->state === self::STATE_REVIEW_REQUIRED;
    }

    public function canBeCanceled(): bool
    {
        return in_array($this->state, [
            self::STATE_UPLOADED,
            self::STATE_ERROR,
            self::STATE_CONFIGURED,
            self::STATE_PENDING,
        ], true);
    }

    public function addLog(UploadQueueLog $log): void
    {
        $this->logs->push($log);
        $this->save();
    }

    public function addStructuredLog(
        string $message,
        UploadQueueLogContextEnums $context = UploadQueueLogContextEnums::INFO,
        UploadQueueLogTypeEnums $type = UploadQueueLogTypeEnums::STATE_CHANGE,
        ?int $state = null,
        ?array $payload = null,
        ?int $userId = null,
    ): void {
        $this->addLog(new UploadQueueLog(
            $message,
            $context,
            now()->toISOString(),
            $userId ? (string) $userId : (Auth::id() ? (string) Auth::id() : null),
            $type,
            $state ?? $this->state,
            $payload,
        ));
    }

    public function transitionToState(
        int $state,
        string $message,
        UploadQueueLogContextEnums $context = UploadQueueLogContextEnums::INFO,
        UploadQueueLogTypeEnums $type = UploadQueueLogTypeEnums::STATE_CHANGE,
        ?array $payload = null,
        ?int $userId = null,
    ): void {
        $this->state = $state;
        $this->save();

        $this->addStructuredLog(
            $message,
            $context,
            $type,
            $state,
            $payload,
            $userId,
        );
    }

    public function hasValidConfig(): bool
    {
        return $this->config->isConfigured();
    }

    public function start(): void
    {
        if (! $this->hasValidConfig()) {
            Notification::make()
                ->title('Upload job cannot be started')
                ->body('Invalid configuration for the job. Please, reconfigure the upload job.')
                ->danger()
                ->send();

            $this->state = self::STATE_UPLOADED;
            $this->save();

            return;
        }

        // Just label as pending and add to the queue to process
        $this->state = self::STATE_PENDING;
        $this->save();

        ProcessUploadQueueRecord::dispatch($this->id, Auth::user());

        Notification::make()
            ->title('Upload job added to queue')
            ->body('The file will be automatically processed. We will notify you by email about the progress.')
            ->success()
            ->persistent()
            ->send();
    }

    public function cancel(): void
    {
        if (! $this->isCancelable()) {
            $this->addLog(
                new UploadQueueLog(
                    'Could not be canceled. State: ['.$this->enumState($this->state).']',
                    UploadQueueLogContextEnums::ERROR,
                    now(),
                    Auth::user()->id
                )
            );

            Notification::make()
                ->title('Upload job cannot be canceled')
                ->body('Only running jobs can be canceled.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Not implemented')
            ->body('Cancel process is not implemented yet.')
            ->warning()
            ->send();

        return;

        $this->state = self::STATE_CANCELED;
        $this->save();
    }

    public function revert(): void
    {
        if (! $this->isRevertible()) {
            $this->addLog(
                new UploadQueueLog(
                    'Could not be reverted. State: ['.$this->enumState($this->state).']',
                    UploadQueueLogContextEnums::ERROR,
                    now(),
                    Auth::user()->id
                )
            );

            Notification::make()
                ->title('Upload job is not revertible')
                ->body('Only finished and not started jobs can be reverted.')
                ->danger()
                ->send();

            return;
        }

        if ($this->state == self::STATE_PENDING) {
            $this->state = self::STATE_CONFIGURED;
            $this->save();

            $this->addLog(
                new UploadQueueLog(
                    'Reverted from state: "'.$this->enumState($this->state).'".',
                    UploadQueueLogContextEnums::WARNING,
                    now(),
                    Auth::user()->id
                )
            );

            Notification::make()
                ->title('Upload job reverted')
                ->success()
                ->send();

            return;
        }

        // TODO
        Notification::make()
            ->title('Not implemented')
            ->body('Revert process is not implemented yet.')
            ->warning()
            ->send();

        return;

        $this->state = self::STATE_CONFIGURED;
        $this->save();
    }
}
