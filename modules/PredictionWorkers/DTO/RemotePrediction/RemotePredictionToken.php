<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Carbon\CarbonImmutable;

final class RemotePredictionToken extends RemotePredictionData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $tokenPrefix,
        public readonly ?string $token,
        public readonly ?CarbonImmutable $expiresAt,
        public readonly ?CarbonImmutable $createdAt,
        public readonly ?CarbonImmutable $revokedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            tokenPrefix: (string) ($data['token_prefix'] ?? ''),
            token: isset($data['token']) ? (string) $data['token'] : null,
            expiresAt: self::date($data['expires_at'] ?? null),
            createdAt: self::date($data['created_at'] ?? null),
            revokedAt: self::date($data['revoked_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'token_prefix' => $this->tokenPrefix,
            'token' => $this->token,
            'expires_at' => $this->expiresAt?->toISOString(),
            'created_at' => $this->createdAt?->toISOString(),
            'revoked_at' => $this->revokedAt?->toISOString(),
        ];
    }
}
