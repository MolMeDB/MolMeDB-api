<?php
namespace App\Rules\UploadFile;

use Illuminate\Contracts\Validation\ValidationRule;

interface ColumnTypeInterface extends ValidationRule
{
    public static function make(): static;
}