<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileToken implements ValidationRule
{
    public function __construct(private readonly ?string $ipAddress = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.enabled', true)) {
            return;
        }

        $secretKey = config('services.turnstile.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            $fail('Captcha verification is not configured.');

            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the captcha verification.');

            return;
        }

        $response = Http::asForm()
            ->timeout(5)
            ->post(config('services.turnstile.verify_url'), [
                'secret' => $secretKey,
                'response' => $value,
                'remoteip' => $this->ipAddress,
            ]);

        if (! $response->ok() || ! $response->json('success')) {
            $fail('Captcha verification failed. Please try again.');
        }
    }
}
