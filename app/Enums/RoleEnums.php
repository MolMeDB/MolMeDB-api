<?php

namespace App\Enums;

enum RoleEnums: string
{
    case ADMIN = 'Administrator';
    case MANAGER = 'Data Manager';
    case EDITOR = 'Contributor';
    case VIEWER = 'Viewer';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            static::ADMIN => 'Administrator',
            static::MANAGER => 'Manager',
            static::EDITOR => 'Editor',
            static::VIEWER => 'Viewer',
        };
    }
}