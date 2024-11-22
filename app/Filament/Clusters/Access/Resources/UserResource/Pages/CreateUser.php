<?php

namespace App\Filament\Clusters\Access\Resources\UserResource\Pages;

use App\Filament\Clusters\Access\Resources\UserResource;
use App\Mail\UserWelcome;
use App\Notifications\WelcomeUser;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $random_password = fake()->password(6);
        $data['password'] = Hash::make('admin');
        $data['onetime_password'] = $random_password;
        return $data;
    }
    protected function afterCreate(): void
    {
        /** @var \App\Models\User */
        $user = $this->record;
        // Notify user about new account details
        // $user->notify(new WelcomeUser($user));

        // Send validation email
        $user->sendEmailVerificationNotification();
    }
}
