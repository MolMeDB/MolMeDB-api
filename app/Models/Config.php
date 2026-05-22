<?php

namespace App\Models;

use Database\Factories\ConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Config extends Model
{
    /** @use HasFactory<ConfigFactory> */
    use HasFactory;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get($key, $default = null)
    {
        if (! Schema::hasTable('configs')) {
            return;
        }

        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set($key, $value)
    {
        if (! Schema::hasTable('configs')) {
            return;
        }

        return (bool) static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
