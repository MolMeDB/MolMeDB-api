<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

enum FileRestrictionType: string
{
    case MEMBRANE = 'membrane';
    case METHOD = 'method';
}

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    const TYPE_COSMO_MEMBRANE = 1;
    const TYPE_IMAGE = 2;

    private static $enumTypes = [
        self::TYPE_COSMO_MEMBRANE => 'Cosmo structure definition',
        self::TYPE_IMAGE => 'Image'
    ];

    private static $enumTypeFolders = [
        self::TYPE_COSMO_MEMBRANE => 'cosmo',
        self::TYPE_IMAGE => 'image'
    ];

    private static $validFileExtensions = [
        self::TYPE_COSMO_MEMBRANE => ['mic'],
        self::TYPE_IMAGE => ['jpg', 'jpeg', 'png', 'svg']
    ];

    public static function enumType($type)
    {
        if(isset(self::$enumTypes[$type]))
            return self::$enumTypes[$type];
        return null;
    }

    protected static function booted()
    {
        static::deleting(function ($file) {
            $otherFilesCount = self::where('path', $file->path)
                ->where('id', '!=', $file->id)
                ->count();

            if($otherFilesCount == 0)
            {
                if (Storage::disk('public')->exists($file->path)) {
                    Storage::disk('public')->delete($file->path);
                }
                else if (Storage::exists($file->path)) {
                    Storage::delete($file->path);
                }
            }
        });

        static::saving(function ($file) {
            $file->hash = md5($file->path);
            $file->user_id = Auth::user()?->id;
            $file->name ??= basename($file->path);
        });
    }

    public static function enumTypes(?FileRestrictionType $restriction = null) : array
    {
        return match($restriction){
            FileRestrictionType::MEMBRANE => array_filter(self::$enumTypes, function($key) {
                return in_array($key, [self::TYPE_COSMO_MEMBRANE, self::TYPE_IMAGE]);
            }, ARRAY_FILTER_USE_KEY),
            FileRestrictionType::METHOD => array_filter(self::$enumTypes, function($key) {
                return in_array($key, [self::TYPE_IMAGE]);
            }, ARRAY_FILTER_USE_KEY),
            default => self::$enumTypes
        };
    }   

    public static function getEnumTypeFolder($type)
    {
        if(isset(self::$enumTypeFolders[$type]))
            return self::$enumTypeFolders[$type];
        return null;
    }

    public function membranes() : BelongsToMany
    {
        return $this->belongsToMany(Membrane::class, 'model_has_files', 'file_id', 'model_id')
            ->wherePivot('model_type', Membrane::class);
    }

    public function name()
    {
        $name = !empty($this->name) ? $this->name : pathinfo($this->path, PATHINFO_FILENAME);
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);
        return "$name.$extension"; 
    }

    public function downloadName() : string
    {
        $membrane = $this->membranes()->first();
        if($membrane && $membrane->abbreviation)
        {
            return $membrane->abbreviation . '_' . 'cosmo.inp';
        }

        return $this->name();
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
