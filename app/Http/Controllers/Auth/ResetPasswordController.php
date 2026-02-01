<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';


    public function __invoke(Request $request, string $token)
    {
        $email = $request->query('email');

        // Validace tokenu bez změny hesla
        $status = Password::tokenExists(
            Password::getUser(['email' => $email]),
            $token
        );

        if (! $status) {
            return redirect(config('app.frontend_url').'/reset-password?error=invalid');
        }

        return redirect(
            config('app.frontend_url')."/reset-password?token={$token}&email={$email}"
        );
    }
}
