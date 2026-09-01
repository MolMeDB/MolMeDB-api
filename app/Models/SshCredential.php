<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SshCredential extends BaseModel
{
    use LogsActivity;

    const AUTH_TYPE_PASSWORD = 'password';
    const AUTH_TYPE_KEY = 'key';

    protected $casts = [
        'port' => 'integer',
        'timeout' => 'integer',
        'password' => 'encrypted',
        'private_key' => 'encrypted',
        'passphrase' => 'encrypted',
    ];

    public static function types() : array
    {
        return [
            self::AUTH_TYPE_PASSWORD => 'Password',
            self::AUTH_TYPE_KEY => 'Key-based',
        ];
    }

    public function fileSystems() : HasMany
    {
        return $this->hasMany(Filesystem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('system')
            ->logOnly([
                'type',
                'name',
                'username',
                'type',
            ])
            ->logOnlyDirty();
    }
}
