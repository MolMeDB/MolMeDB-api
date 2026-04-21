<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $email = $request->query('email');
        $user = Password::getUser(['email' => $email]);

        if (! $user || ! Password::tokenExists($user, $token)) {
            return redirect(config('app.frontend_url').'/reset-password?error=invalid');
        }

        return redirect(config('app.frontend_url')."/password-reset/{$token}?email=".urlencode((string) $email));
    }
}
