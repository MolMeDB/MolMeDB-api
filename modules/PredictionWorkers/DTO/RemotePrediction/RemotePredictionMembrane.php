<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Carbon\CarbonImmutable;

final class RemotePredictionMembrane extends RemotePredictionData
{
    public function __construct(
        public readonly string $key,
        public readonly string $md5,
        public readonly bool $fileValid,
        public readonly ?CarbonImmutable $createdAt,
        public readonly ?CarbonImmutable $updatedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            md5: (string) ($data['md5'] ?? ''),
            fileValid: (bool) ($data['file_valid'] ?? false),
            createdAt: self::date($data['created_at'] ?? null),
            updatedAt: self::date($data['updated_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'md5' => $this->md5,
            'file_valid' => $this->fileValid,
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }
}
