<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Carbon\CarbonImmutable;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Enums\RemotePredictionStep;

final class RemotePredictionStepSnapshot extends RemotePredictionData
{
    public function __construct(
        public readonly RemotePredictionStep|string $step,
        public readonly RemotePredictionStatus|string $status,
        public readonly int $attempts,
        public readonly ?string $message,
        public readonly ?CarbonImmutable $startedAt,
        public readonly ?CarbonImmutable $finishedAt,
        public readonly ?CarbonImmutable $heartbeatAt,
        public readonly ?int $heartbeatAgeSeconds,
        public readonly ?CarbonImmutable $leaseExpiresAt,
        public readonly ?string $workerId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $step = (string) ($data['step'] ?? '');
        $status = (string) ($data['status'] ?? '');

        return new self(
            step: RemotePredictionStep::tryFrom($step) ?? $step,
            status: RemotePredictionStatus::tryFrom($status) ?? $status,
            attempts: (int) ($data['attempts'] ?? 0),
            message: isset($data['message']) ? (string) $data['message'] : null,
            startedAt: self::date($data['started_at'] ?? null),
            finishedAt: self::date($data['finished_at'] ?? null),
            heartbeatAt: self::date($data['heartbeat_at'] ?? null),
            heartbeatAgeSeconds: isset($data['heartbeat_age_seconds'])
                ? (int) $data['heartbeat_age_seconds']
                : null,
            leaseExpiresAt: self::date($data['lease_expires_at'] ?? null),
            workerId: isset($data['worker_id'])
                ? (string) $data['worker_id']
                : (isset($data['lease_owner']) ? (string) $data['lease_owner'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'step' => $this->step instanceof RemotePredictionStep ? $this->step->value : $this->step,
            'status' => $this->status instanceof RemotePredictionStatus ? $this->status->value : $this->status,
            'attempts' => $this->attempts,
            'message' => $this->message,
            'started_at' => $this->startedAt?->toISOString(),
            'finished_at' => $this->finishedAt?->toISOString(),
            'heartbeat_at' => $this->heartbeatAt?->toISOString(),
            'heartbeat_age_seconds' => $this->heartbeatAgeSeconds,
            'lease_expires_at' => $this->leaseExpiresAt?->toISOString(),
            'worker_id' => $this->workerId,
        ];
    }
}
