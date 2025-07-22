<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

enum FileRestrictionType: string
{
    case MEMBRANE = 'membrane';
    case METHOD = 'method';
}

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /** SPECIAL TYPES OF FILES FOR EASY FINDING */
    const TYPE_STATS_INTERACTIONS_ALL = 1;
    const TYPE_STATS_ACTIVE_PASSIVE = 2;
    const TYPE_STATS_IDENTIFIERS = 3;
    const TYPE_STATS_SUBST_INTERACTIONS = 4;

    /** RDF SPECIAL FILES */
    const TYPE_RDF_VOCABULARY = 100;
    const TYPE_EXAMPLE_ENERGY = 51;

    /** UPLOAD FILES */
    const TYPE_UPLOAD_PASSIVE  = 20;
    const TYPE_UPLOAD_ACTIVE   = 21;
    const TYPE_UPLOAD_ENERGY   = 22;

    /** SCHEDULER REPORTS */
    const TYPE_SCHEDULER_REPORT = 30;
    const TYPE_SCHEDULER_DEL_EMPTY_SUBSTANCES = 31;
    const TYPE_SCHEDULER_CHECK_PASSIVE_DATASETS = 32;

    /** MEMBRANE SPECIAL FILES */
    const TYPE_COSMO_MEMBRANE = 41;

    /** IMAGE FILES */
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
            $realPath = null;
            if(Storage::disk('public')->exists($file->path))
            {
                $realPath = Storage::disk('public')->path($file->path);
            }
            else if (Storage::disk('private')->exists($file->path)) {
                $realPath = Storage::disk('private')->path($file->path);
            }
            else
            {
                throw new Exception('File does not exist.');
            }

            $file->hash = File::hash($realPath);
            $file->user_id = Auth::user()?->id;
            $file->mime = FacadesFile::mimeType($realPath);
            // $file->setAttribute('name', $file->name ?? basename($file->path));
            // $file->name ??= basename($file->path);
        });
    }

    public function existsOnDisk($disk = 'public') : bool
    {
        return Storage::disk($disk)->exists($this->path);
    }

    public static function getUniqueNameForSave(TemporaryUploadedFile $file, string $targetFolder, string $disk) : string 
    {
        $originalName = pathinfo(basename($file->getClientOriginalName()), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        //remove suffix
        $name = $originalName;
        $i = 1;
        while (Storage::disk($disk)->exists(trim($targetFolder, '/') . '/' . $name . '.' . $extension)) {
            $name = pathinfo($originalName, PATHINFO_FILENAME) . "_$i";
            $i++;
        }
        return $name . '.' . $extension;
    }

    public static function hash($path)
    {
        return md5_file($path);
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
