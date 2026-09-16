<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\TurnstileToken;
use App\Services\EmailLoginService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): Response
    {
        $email = strtolower((string) $request->input('email', ''));
        $existingGhost = User::withTrashed()
            ->where('email', $email)
            ->where('is_ghost', true)
            ->whereNull('deleted_at')
            ->first();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'affiliation' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                // Allow the email if it belongs to a ghost user (will be activated)
                $existingGhost ? Rule::unique(User::class)->ignore($existingGhost->id) : Rule::unique(User::class),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'turnstile_token' => ['required', 'string', new TurnstileToken($request->ip())],
        ]);

        if ($existingGhost) {
            $user = app(EmailLoginService::class)->activatePasswordAccount(
                user: $existingGhost,
                name: $request->name,
                affiliation: $request->affiliation,
                hashedPassword: Hash::make($request->string('password')),
            );
        } else {
            $user = User::create([
                'name' => $request->name,
                'affiliation' => $request->affiliation,
                'email' => $request->email,
                'password' => Hash::make($request->string('password')),
            ]);
        }

        event(new Registered($user));

        return response()->noContent();
    }
}
