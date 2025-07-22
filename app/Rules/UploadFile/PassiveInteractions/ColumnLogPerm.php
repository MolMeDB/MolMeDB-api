<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Models\Method;
use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Facades\Validator;

class ColumnLogPerm implements ColumnTypeInterface, DataAwareRule
{
    public static string $key = 'logperm';
    public static string $label = 'LogPerm';

    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;
 
        return $this;
    }

    public static function make(): static
    {
        return new static();
    }

    public function isOutOfLimits($value, Method $method): bool 
    {
        $limits = $method->parameters?->alert_limits;

        if($limits && isset($limits['logperm']) && 
            is_numeric($limits['logperm']['min']) && 
            is_numeric($limits['logperm']['max']))
        {
            if($value < $limits['logperm']['min'] || $value > $limits['logperm']['max'])
            {
                return true;
            }
        }

        return false;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        // use native numeric validator
        $validator = Validator::make(
            [$attribute => $value],
            [$attribute => 'numeric']
        );

        if ($validator->fails()) {
            $fail("Column " . self::$label . " must be a number.");
        }
    }
}