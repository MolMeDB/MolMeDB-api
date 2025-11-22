<?php
namespace Modules\PredictionWorkers\Traits;

use App\Models\Filesystem;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isResource;

trait HasRemoteConformers
{
    use HasRemoteContent;

    // Cache results
    protected ?array $remoteConformersPaths = null;
    protected ?array $remoteCosmoPaths = null;
    protected static ?string $disk = null;

    const CONFORMERS_FOLDER = '01-INPUT';
    const OPTIMIZATIONS_FOLDER = '02-OPTIMIZE';
    const COSMO_INPUT_FOLDER = '03-COSMO_INPUT';
    const COSMO_OUTPUT_FOLDER = '04-COSMO_RESULTS';

    public function totalRemoteConformers()
    {
        if(!$this->base_path)
        {
            return null;
        }

        return count($this->remoteConformersFilePaths());
    }

    public static function disk() : string | null
    {
        if(!self::$disk)
        {
            self::$disk = Filesystem::where('type', Filesystem::TYPE_PREDICTIONS_METACENTRUM)->first()?->systemName;
        }

        return self::$disk;
    }

    public function remoteConformersFilePaths()
    {
        if(!$this->base_path)
        {
            return null;
        }

        if(!$this->remoteConformersPaths)
        {
            $this->remoteConformersPaths = array_filter($this->remoteFiles($this->base_path . self::CONFORMERS_FOLDER), function($file) {
                return strpos($file, '.sdf') !== false;
            });
        }

        return $this->remoteConformersPaths;
    }

    public function cosmoFilePaths()
    {
        if(!$this->base_path)
        {
            return null;
        }

        if(!$this->remoteCosmoPaths)
        {
            $this->remoteCosmoPaths = array_filter($this->remoteFiles($this->base_path . self::COSMO_INPUT_FOLDER), function($file) {
                return strpos($file, '.ccf') !== false;
            });
        }

        return $this->remoteCosmoPaths;
    }

    public function fileNamesWithoutSuffix()
    {
        $files = $this->remoteConformersFilePaths();

        return array_map(function($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        }, $files);
    }

    public function isOptimizationDefined()
    {
        if(!$this->base_path)
        {
            return null;
        }

        $files = $this->fileNamesWithoutSuffix();

        $hasMissing = false;

        foreach($files as $file)
        {
            if(!$this->remoteExists($this->base_path . self::OPTIMIZATIONS_FOLDER . '/' . $file . '/' . $file . '.job'))
            {
                $hasMissing = true;
                break;
            }
        }

        return !$hasMissing;
    }

    public function totalOptimizationsFinished()
    {
        if(!$this->base_path)
        {
            return null;
        }

        $files = $this->fileNamesWithoutSuffix();

        $total = 0;

        foreach($files as $file)
        {
            $base = $this->base_path . self::OPTIMIZATIONS_FOLDER . '/' . $file . '/OUTPUT/';

            $steps = $this->remoteDirs($base);

            if(!count($steps))
            {
                continue;
            }

            // Order by name
            rsort($steps);

            $last = str_replace($base, '', $steps[0]);

            if($this->remoteExists($base . $last . '/out.ccf'))
            {
                $total++;
            }
        }

        return $total;
    }

    public function areOptimizationsFinished()
    {
        if(!$this->base_path)
        {
            return null;
        }

        $files = $this->fileNamesWithoutSuffix();

        return count($files) == $this->totalOptimizationsFinished();
    }

    public function totalPreparedCosmoFiles()
    {
        if(!$this->base_path)
        {
            return null;
        }

        $files = $this->fileNamesWithoutSuffix();
        $cosmoFiles = $this->cosmoFilePaths();

        return count(array_filter($cosmoFiles, function($cosmoFile) use ($files) {
            return in_array(pathinfo($cosmoFile, PATHINFO_FILENAME), $files);
        }));
    }

    public function arePreparedCosmoFiles()
    {
        return $this->totalPreparedCosmoFiles() == $this->totalRemoteConformers();
    }

    public static function getCosmoFolderName(\Modules\PredictionWorkers\Models\Prediction $prediction)
    {
        return $prediction::$enum_method_shorts[$prediction->method_type] 
            . '_' 
            . str_replace('/', '_', $prediction->predictionMembrane->abbreviation)
            . '_'
            . str_replace('.', ',',  number_format($prediction->temperature, 1));
    }

    public static function getLocalCosmoFolderPath(\Modules\PredictionWorkers\Models\Prediction $prediction)
    {
        return $prediction->predictionStructure->id
            . '/' 
            . $prediction::$enum_method_shorts[$prediction->method_type] 
            . '_' 
            . str_replace('/', '_', $prediction->predictionMembrane->abbreviation)
            . '_'
            . str_replace('.', ',',  number_format($prediction->temperature, 1))
            . '/';
    }

    public static function getLocalCosmoFilePath(\Modules\PredictionWorkers\Models\Prediction $prediction)
    {
        return $prediction->predictionStructure->id
            . '/' 
            . $prediction::$enum_method_shorts[$prediction->method_type] 
            . '_' 
            . str_replace('/', '_', $prediction->predictionMembrane->abbreviation)
            . '_'
            . str_replace('.', ',',  number_format($prediction->temperature, 1))
            . '/'
            . 'cosmo.xml';
    }

    public function hasCosmoResults(\Modules\PredictionWorkers\Models\Prediction $prediction)
    {
        if(!$this->base_path)
        {
            return null;
        }

        $base = $this->base_path . self::COSMO_OUTPUT_FOLDER . '/' . self::getCosmoFolderName($prediction) . '/';

        return count(
            array_filter($this->remoteFiles($base), function($file) {
                return strpos($file, 'cosmo.xml') !== false;
            })
            ) > 0;
    }

    public function saveCosmoResult(\Modules\PredictionWorkers\Models\Prediction $prediction)
    {
        if(!$this->base_path)
        {
            return null;
        }

        $base = $this->base_path . self::COSMO_OUTPUT_FOLDER . '/' . self::getCosmoFolderName($prediction) . '/';

        $src = $this->remote();
        $dst = Storage::disk(self::disk());
        $dst_folder = self::getLocalCosmoFolderPath($prediction);

        if(!$dst->exists($dst_folder))
        {
            $dst->makeDirectory($dst_folder);
        }

        $input = $src->readStream($base . 'cosmo.xml');

        if(!$input)
        {
            return null;
        }

        $ok = method_exists($dst, 'writeStream')
            ? $dst->writeStream(self::getLocalCosmoFilePath($prediction), $input)
            : $dst->put(self::getLocalCosmoFilePath($prediction), $input);

        if(isResource($input))
        {
            fclose($input);
        }

        return (boolean) $ok;
    }
}
