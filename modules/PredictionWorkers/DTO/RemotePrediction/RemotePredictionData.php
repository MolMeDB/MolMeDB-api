<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class RemotePredictionData implements Arrayable, JsonSerializable
{
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected static function date(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)
            : null;
    }
}
