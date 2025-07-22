<?php
namespace App\Rules\UploadFile;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Facades\Validator;

class ColumnAccuracy implements ColumnTypeInterface, DataAwareRule
{
    public static string $key = 'accuracy';
    public static string $label = 'Accuracy';

    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    public static function make(): static
    {
        return new static();
    }

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

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $parent = str_replace('_acc', '', $this::$key);

        if(!isset($this->data[$parent]))
        {
            $fail("Column '" . $parent . "' is required if '" . $this::$label . "' is present.");
        }

        // use native numeric validator
        $validator = Validator::make(
            [$attribute => $value],
            [$attribute => 'numeric|min:0']
        );

        if ($validator->fails()) {
            $fail("Column " . $this::$label . " must be a positive number.");
        }
    }
}