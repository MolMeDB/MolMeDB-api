<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Carbon\CarbonImmutable;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;

final class RemotePredictionCalculation extends RemotePredictionData
{
    /**
     * @param  array<string, mixed>|null  $result
     */
    public function __construct(
        public readonly string $id,
        public readonly string $moleculeId,
        public readonly string $membraneKey,
        public readonly float $temperatureC,
        public readonly RemotePredictionStatus|string $status,
        public readonly ?string $workDir,
        public readonly ?array $result,
        public readonly int $attempts,
        public readonly ?string $message,
        public readonly ?CarbonImmutable $startedAt,
        public readonly ?CarbonImmutable $finishedAt,
        public readonly ?CarbonImmutable $heartbeatAt,
        public readonly ?int $heartbeatAgeSeconds,
        public readonly ?CarbonImmutable $leaseExpiresAt,
        public readonly ?CarbonImmutable $downloadRequestedAt,
        public readonly ?CarbonImmutable $downloadedAt,
        public readonly ?CarbonImmutable $createdAt,
        public readonly ?CarbonImmutable $updatedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $status = (string) ($data['status'] ?? '');

        return new self(
            id: (string) ($data['id'] ?? ''),
            moleculeId: (string) ($data['molecule_id'] ?? ''),
            membraneKey: (string) ($data['membrane_key'] ?? ''),
            temperatureC: (float) ($data['temperature_c'] ?? 0),
            status: RemotePredictionStatus::tryFrom($status) ?? $status,
            workDir: isset($data['work_dir']) ? (string) $data['work_dir'] : null,
            result: is_array($data['result'] ?? null)
                ? RemotePredictionPayload::parse($data['result'])
                : null,
            attempts: (int) ($data['attempts'] ?? 0),
            message: isset($data['message']) ? (string) $data['message'] : null,
            startedAt: self::date($data['started_at'] ?? null),
            finishedAt: self::date($data['finished_at'] ?? null),
            heartbeatAt: self::date($data['heartbeat_at'] ?? null),
            heartbeatAgeSeconds: isset($data['heartbeat_age_seconds'])
                ? (int) $data['heartbeat_age_seconds']
                : null,
            leaseExpiresAt: self::date($data['lease_expires_at'] ?? null),
            downloadRequestedAt: self::date($data['download_requested_at'] ?? null),
            downloadedAt: self::date($data['downloaded_at'] ?? null),
            createdAt: self::date($data['created_at'] ?? null),
            updatedAt: self::date($data['updated_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'molecule_id' => $this->moleculeId,
            'membrane_key' => $this->membraneKey,
            'temperature_c' => $this->temperatureC,
            'status' => $this->status instanceof RemotePredictionStatus ? $this->status->value : $this->status,
            'work_dir' => $this->workDir,
            'result' => RemotePredictionPayload::serialize($this->result),
            'attempts' => $this->attempts,
            'message' => $this->message,
            'started_at' => $this->startedAt?->toISOString(),
            'finished_at' => $this->finishedAt?->toISOString(),
            'heartbeat_at' => $this->heartbeatAt?->toISOString(),
            'heartbeat_age_seconds' => $this->heartbeatAgeSeconds,
            'lease_expires_at' => $this->leaseExpiresAt?->toISOString(),
            'download_requested_at' => $this->downloadRequestedAt?->toISOString(),
            'downloaded_at' => $this->downloadedAt?->toISOString(),
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }
}
