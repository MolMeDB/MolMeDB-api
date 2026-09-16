<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response|JsonResponse|UserResource
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        return UserResource::make($user)->additional([
            'meta' => [
                'remember' => $request->boolean('remember'),
                'session_lifetime_minutes' => (int) Config::get('session.lifetime'),
                'session_expires_at' => now()
                    ->addMinutes((int) Config::get('session.lifetime'))
                    ->toISOString(),
            ],
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
