<?php

namespace App\Casts;

use App\ValueObjects\UploadQueueConfig;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class UploadQueueConfigCasts implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): UploadQueueConfig
    {
        if ($value === null || $value === '') {
            return new UploadQueueConfig;
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        return UploadQueueConfig::fromArray(is_array($decoded) ? $decoded : []);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof UploadQueueConfig) {
            return $value->toJson();
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('The value must be an array or an instance of '.UploadQueueConfig::class.'.');
        }

        $json = json_encode($value);

        return is_string($json) ? $json : '{}';
    }
}
