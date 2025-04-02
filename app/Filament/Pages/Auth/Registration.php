<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login;
use Filament\Pages\Auth\Register;
use Filament\Pages\Page;

class Registration extends Register
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('First name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('last_name')
                            ->label(__('Last name'))
                            ->required()
                            ->maxLength(255),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    public function afterRegister() : void 
    {
        Notification::make()
            ->title('Registration successful')
            ->body('Please check your email to verify your account.')
            ->success()
            ->send();
    }
}
