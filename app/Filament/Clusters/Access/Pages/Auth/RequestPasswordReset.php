<?php

namespace App\Filament\Clusters\Access\Pages\Auth;

use App\Filament\Clusters\Access;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset; 

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected static ?string $cluster = Access::class;
}
