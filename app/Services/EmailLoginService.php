<?php

namespace App\Services;

use App\Models\FeedbackEmailVerification;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmailLoginService
{
    public function authenticate(
        Request $request,
        FeedbackEmailVerification $verification,
        string $email,
    ): User {
        [$user, $wasUnverified] = DB::transaction(function () use ($verification, $email): array {
            $lockedVerification = FeedbackEmailVerification::query()
                ->whereKey($verification->getKey())
                ->whereNull('verified_at')
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $lockedVerification) {
                throw ValidationException::withMessages([
                    'code' => ['Verification code is invalid or has already been used.'],
                ]);
            }

            $user = User::withTrashed()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($user?->trashed()) {
                throw ValidationException::withMessages([
                    'email' => ['This account is not available.'],
                ]);
            }

            if (! $user) {
                $user = User::query()->create([
                    'name' => $email,
                    'email' => $email,
                    'password' => null,
                    'is_ghost' => true,
                    'email_verified_at' => now(),
                ]);
                $wasUnverified = false;
            } else {
                $wasUnverified = ! $user->hasVerifiedEmail();
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $lockedVerification->forceFill([
                'user_id' => $user->id,
                'verified_at' => now(),
                'consumed_at' => now(),
            ])->save();

            return [$user->fresh(), $wasUnverified];
        });

        if ($wasUnverified) {
            event(new Verified($user));
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $user;
    }

    public function activatePasswordAccount(
        User $user,
        string $name,
        ?string $affiliation,
        string $hashedPassword,
    ): User {
        $user->forceFill([
            'name' => $name,
            'affiliation' => $affiliation,
            'password' => $hashedPassword,
            'is_ghost' => false,
            'email_verified_at' => null,
        ])->save();

        return $user->fresh();
    }
}
