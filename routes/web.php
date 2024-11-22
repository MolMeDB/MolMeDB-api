<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    // Redirect to filament admin
    return redirect('/admin');
})->name('login');

Route::post('/logout', function () {
    Filament::auth()->logout();

    return redirect('/login');
})->name('logout');

// For testing email messages
Route::get('/email', function () {
    $user = App\Models\User::first();

    return view('mail.welcome', [
        'user' => $user,
        'app_name' => config('app.name'),
        'validation_link' => 'http://test.molmedb.org', // TODO
        'app_url' => 'http://test.molmedb.org',
    ]);
});

/**
 * Show when user tries to access a page that requires email verification
 */
Route::get('/email/verify', function() {
    return view('auth.verify');
})->middleware('auth')->name('verification.notice');


Route::get('/admin/verify/{id}/{hash}', function(EmailVerificationRequest $r) {
    $r->fulfill();

    return redirect()->route('login');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $r) {

    $r->user()->sendEmailVerificationNotification();

    return back()->with('resent', 'Verification link sent ');
})->middleware(['auth', 'throttle:6,1'])->name('verification.resend');

