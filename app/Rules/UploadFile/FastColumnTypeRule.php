<?php

namespace App\Rules\UploadFile;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class FastColumnTypeRule implements DataAwareRule, ValidationRule
{
    public function __construct(private readonly ColumnTypeInterface $rule) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        if ($this->rule instanceof DataAwareRule) {
            $this->rule->setData($data);
        }

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->rule->validate_fast($attribute, $value, $fail);
    }
}
