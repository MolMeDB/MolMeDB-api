<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\EditProfile;

class Profile extends EditProfile
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
                            ->minLength(2)
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('last_name')
                            ->label(__('Last name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }
}
