<?php

namespace App\ValueObjects;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

class UploadQueueLog extends Model implements JsonSerializable
{
    public UploadQueueLogTypeEnums $type;

    public function __construct(
        public string $message,
        UploadQueueLogContextEnums|string $context,
        public ?string $timestamp = null,
        public ?string $user_id = null,
        UploadQueueLogTypeEnums|string|null $type = null,
        public ?int $state = null,
        public ?array $payload = null,
    ) {
        $this->context = is_string($context) ? UploadQueueLogContextEnums::from($context) : $context;
        $this->type = is_string($type)
            ? (UploadQueueLogTypeEnums::tryFrom($type) ?? UploadQueueLogTypeEnums::STATE_CHANGE)
            : ($type ?? UploadQueueLogTypeEnums::STATE_CHANGE);
        $this->message = $message;
        $this->timestamp = $timestamp;
        $this->user_id = $user_id;
        $this->state = $state;
        $this->payload = $payload;
    }

    public function jsonSerialize(): array
    {
        return [
            'message' => $this->message,
            'context' => $this->context->value,
            'timestamp' => $this->timestamp,
            'user_id' => $this->user_id,
            'type' => $this->type->value,
            'state' => $this->state,
            'payload' => $this->payload,
        ];
    }

    public static function remapOldLogs($data): array
    {
        if (self::hasValidListStructure($data)) {
            return $data;
        }

        if (! is_array($data)) {
            return [];
        }

        $result = [];

        if (isset($data['error'])) {
            foreach (self::normalizeLegacyMessages($data['error']) as $message) {
                $result[] = new UploadQueueLog($message, UploadQueueLogContextEnums::ERROR, now()->toISOString(), null, UploadQueueLogTypeEnums::VALIDATION_RUN);
            }

            unset($data['error']);
        }

        if (isset($data['warning'])) {
            foreach (self::normalizeLegacyMessages($data['warning']) as $message) {
                $result[] = new UploadQueueLog($message, UploadQueueLogContextEnums::WARNING, now()->toISOString(), null, UploadQueueLogTypeEnums::VALIDATION_RUN);
            }

            unset($data['warning']);
        }

        if (isset($data['success'])) {
            foreach (self::normalizeLegacyMessages($data['success']) as $message) {
                $result[] = new UploadQueueLog($message, UploadQueueLogContextEnums::SUCCESS, now()->toISOString(), null, UploadQueueLogTypeEnums::VALIDATION_RUN);
            }

            unset($data['success']);
        }

        foreach ($data as $obj) {
            if (is_object($obj) && isset($obj->message) && isset($obj->context)) {
                $result[] = new UploadQueueLog(
                    $obj->message,
                    UploadQueueLogContextEnums::from($obj->context),
                    isset($obj->timestamp) ? $obj->timestamp : null,
                    isset($obj->user_id) ? $obj->user_id : null,
                    isset($obj->type) ? $obj->type : UploadQueueLogTypeEnums::STATE_CHANGE,
                    isset($obj->state) ? intval($obj->state) : null,
                    isset($obj->payload) && is_array($obj->payload) ? $obj->payload : null
                );
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private static function normalizeLegacyMessages(mixed $messages): array
    {
        if (is_array($messages)) {
            return array_values(array_filter($messages, fn (mixed $message): bool => is_string($message)));
        }

        if (is_string($messages) && trim($messages) !== '') {
            return [$messages];
        }

        return [];
    }

    public static function hasValidListStructure($data): bool
    {
        if (! is_array($data)) {
            return false;
        } elseif (! count($data)) {
            return true;
        }

        if (! isset($data[0]) ||
            (is_array($data[0]) && (! isset($data[0]['message']) || ! isset($data[0]['context']) || ! isset($data[0]['timestamp']))) ||
            (is_object($data[0]) && (! isset($data[0]->message) || ! isset($data[0]->context) || ! isset($data[0]->timestamp)))) {
            return false;
        }

        return true;
    }
}
