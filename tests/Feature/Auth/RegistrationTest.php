<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('new users can register', function () {
    config()->set('services.turnstile.enabled', false);
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'affiliation' => 'Test Institute',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'turnstile_token' => 'test-token',
    ]);

    $response->assertNoContent();

    // RegisteredUserController does not log the new user in — the app
    // requires email verification before login (see LoginForm's
    // "unverified" handling), so the request only creates the account
    // and queues a verification email.
    $this->assertGuest();
    $user = User::where('email', 'test@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
});
