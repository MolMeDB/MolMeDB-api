<?php

namespace App\Enums;

enum PermissionEnums: string
{
    case ADMIN_PANEL = 'admin.panel';

    case CATEGORIES_VIEW = 'categories.view';
    case CATEGORIES_MANAGE = 'categories.manage';
    case CATEGORIES_MANAGE_OWN = 'categories.manage.own';

    case SETTINGS_VIEW = 'settings.view';
    case SETTINGS_EDIT = 'settings.edit';
    
    case MEMBRANE_METHOD_VIEW = 'membrane.method.view';
    case MEMBRANE_METHOD_EDIT = 'membrane.method.edit';
    case MEMBRANE_METHOD_EDIT_OWN = 'membrane.method.edit.own';
    case MEMBRANE_METHOD_DELETE = 'membrane.method.delete';
    case MEMBRANE_METHOD_DELETE_OWN = 'membrane.method.delete.own';

    case DATASET_VIEW = 'dataset.view';
    case DATASET_EDIT = 'dataset.edit';
    case DATASET_DELETE = 'dataset.delete';
    case DATASET_DELETE_OWN = 'dataset.delete.own';
    case DATASET_DELETE_FORCE = 'dataset.delete.force';

    case USERS_VIEW = 'users.view';
    case USERS_EDIT = 'users.edit';
    case USERS_DELETE = 'users.delete';

    case ROLES_VIEW = 'roles.view';
    case ROLES_ASSIGN = 'roles.assign';
    case ROLES_EDIT = 'roles.edit';
    case ROLES_DELETE = 'roles.delete';


    public function description(): string
    {
        return match ($this) {
            static::ADMIN_PANEL => 'Can access the admin dashboard',
            static::CATEGORIES_VIEW => 'Can view categories',
            static::CATEGORIES_MANAGE => 'Can manage categories',
            static::CATEGORIES_MANAGE_OWN => 'Can manage own categories',
            static::DATASET_VIEW => 'Can view datasets',
            static::DATASET_EDIT => 'Can manage datasets',
            static::DATASET_DELETE => 'Can delete datasets',
            static::DATASET_DELETE_OWN => 'Can delete own datasets',
            static::DATASET_DELETE_FORCE => 'Can force delete datasets',
            static::MEMBRANE_METHOD_VIEW => 'Can view membranes, methods, publications and protein targets',
            static::MEMBRANE_METHOD_EDIT => 'Can manage membranes, methods, publications and protein targets',
            static::MEMBRANE_METHOD_EDIT_OWN => 'Can manage own membranes, methods, publications and protein targets',
            static::MEMBRANE_METHOD_DELETE => 'Can delete any membranes, methods, publications and protein targets',
            static::MEMBRANE_METHOD_DELETE_OWN => 'Can delete own membrane, method, publications and protein targets records',
            static::SETTINGS_EDIT => 'Can manage settings',
            static::SETTINGS_VIEW => 'Can view settings',
            static::ROLES_VIEW => 'Can see user roles',
            static::ROLES_ASSIGN => 'Can assign roles to users',
            static::ROLES_EDIT => 'Can manage roles',
            static::ROLES_DELETE => 'Can delete existing roles',
            static::USERS_VIEW => 'Can view users',
            static::USERS_EDIT => 'Can manage basic user details',
            static::USERS_DELETE => 'Can soft-delete user',
        };
    }

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return str_replace('.', ' ', $this->value);
    }
}