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

    public const KEY_FEEDBACK_EMAIL_FALLBACK = 'feedback:email_fallback';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => 'string',
            'value' => 'string',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('configs')) {
            return $default;
        }

        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): bool
    {
        if (! Schema::hasTable('configs')) {
            return false;
        }

        return (bool) static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
