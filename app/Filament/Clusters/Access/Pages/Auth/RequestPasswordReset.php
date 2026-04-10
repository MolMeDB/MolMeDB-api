<?php

namespace App\Filament\Clusters\Access\Pages\Auth;

use App\Filament\Clusters\Access; 

class RequestPasswordReset extends \Filament\Auth\Pages\PasswordReset\RequestPasswordReset
{
    protected static ?string $cluster = Access::class;
}
