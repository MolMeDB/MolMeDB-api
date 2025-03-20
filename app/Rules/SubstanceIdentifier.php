<?php

namespace App\Rules;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class SubstanceIdentifier implements ValidationRule
{
    /**
     * Constructor
     */
    public function __construct(private Model $substance, private int|string $type){}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // TODO: Check if value has valid format for given type

        // At first check, if the value already exists for given molecule

        // Check, if exists for other molecules
        // If yes, return message with warning about future join

        // $fail('test');
        // Notification::make()
        //     ->title('Error')
        //     ->danger()
        //     ->persistent()
        //     ->body('Test')
        //     ->send();

    }
}
