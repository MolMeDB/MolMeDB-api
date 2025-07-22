<?php

namespace App\Casts;

use App\ValueObjects\MethodParameters;
use Exception;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MethodParametersCasts implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return new MethodParameters($value ? json_decode($value, true) : []);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if(! $value instanceof MethodParameters) {
            throw new Exception('The value must be an instance of ' . MethodParameters::class);
        }

        return json_encode($value->toArray());
    }
}
