<?php
namespace App\Libraries;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use stdClass;

class ExportToFile 
{
    const PREFIX = 'download/';

    const CONTEXT_MEMBRANE = 'membrane';
    const CONTEXT_METHOD = 'method';
    const CONTEXT_PUBLICATION = 'publication';

    const TYPE_CSV = 'csv';

    private ?string $fullFilePath = null;
    private $fileHandler = null;

    private $header = null;
    private $isHeaderWritten = false;
    protected static $storage;


    public function __construct(
        private string $context,
        public ?string $filename = null,
        private ?string $folder = null,
        private string $filetype = self::TYPE_CSV
    ) {
        self::$storage = Storage::disk('public');
        if(!$filename)
        {
            $this->filename = date('Y-m-d');
        }
    }

    public function getTargetFolder()
    {
        return self::PREFIX 
            . trim($this->context, '/')
            . '/' 
            . trim($this->folder, '/');
        // return public_path(
        //     self::PREFIX 
        //     . trim($this->context, '/')
        //     . '/' 
        //     . trim($this->folder, '/')
        // );
    }

    public function getFileName()
    {
        $this->fullFilePath =
            $this->getTargetFolder() 
            . '/'
            . ltrim($this->filename, '/')
            . $this->getSuffix();

        return $this->fullFilePath;
    }

    public function getSuffix()
    {
        return match($this->filetype)
        {
            self::TYPE_CSV => '.csv'
        };
    }

    public function initFile($mode = 'w')
    {
        if(!$this->fullFilePath)
        {
            $this->getFileName();
        }

        // if(!is_dir(dirname($this->fullFilePath)))
        // {
        //     mkdir(dirname($this->fullFilePath), 0777, true);
        // }

        // $this->fileHandler = fopen($this->fullFilePath, $mode);

        self::$storage->put($this->fullFilePath, '');
        $this->fileHandler = fopen(self::$storage->path($this->fullFilePath), $mode);

        if($this->fileHandler === false)
        {
            throw new \Exception('Could not open file: ' . $this->fullFilePath);
        }

        return $this;
    }

    public function setHeader(ExportFileHeader $header)
    {
        $this->header = $header;
        $this->isHeaderWritten = false;
        return $this;
    }

    public function writeHeader()
    {
        if(!$this->header)
        {
            return false;
        }

        if($this->fileHandler === null)
        {
            $this->initFile();
        }

        if(!$this->isHeaderWritten)
        {
            $this->writeRow($this->header->as_array());
            $this->isHeaderWritten = true;
        }

        return $this;
    }

    public function writeRow(Model |stdClass | array | string $row, $separator = ';')
    {
        if($this->fileHandler === null)
        {
            $this->initFile();
        }

        if(is_array($row))
        {
            fputcsv($this->fileHandler, $row, $separator);
        }
        else if(is_string($row))
        {
            fwrite($this->fileHandler, $row . PHP_EOL);
        }
        else
        {
            if(!$this->header || !$this->isHeaderWritten)
            {
                throw new Exception('Header is not written to the target file.');
            }

            $toWrite = '';
            foreach($this->header->items as $column)
            {
                $toWrite .= $column->getValue($row) . $separator;
            }

            // Remove last character [;]
            $toWrite = substr($toWrite, 0, -1);
            fwrite($this->fileHandler, $toWrite . PHP_EOL);
        }

        return $this;
    }

    public function closeFile()
    {
        if ($this->fileHandler !== null) {
            try {
                if (!fclose($this->fileHandler)) {
                    // Nepodařilo se zavřít soubor, log nebo další akce
                    trigger_error('Failed to close the file.', E_USER_WARNING);
                }
            } catch (\Throwable $e) {
                // Ošetření neočekávané chyby
                trigger_error('Error closing file: ' . $e->getMessage(), E_USER_WARNING);
            }
            $this->fileHandler = null;
        }

        return $this;
    }

    public function getZipFilePath()
    {
        return $this->getTargetFolder() . '/' . $this->filename . '.zip';
    }

    /**
     * @deprecated
     */
    public function getRelativeZipFilePath()
    {
        return $this->getZipFilePath();
        // return ltrim(str_replace(public_path(), '', $this->getZipFilePath()), '');
    }

    public function checkIfFileIsNonempty()
    {
        if (!$this->fullFilePath
            || !self::$storage->exists($this->fullFilePath)) {
            trigger_error('File is not initialized or does not exist.', E_USER_WARNING);
            return false;
        }

        $lineCount = 0;

        $handle = fopen(self::$storage->path($this->fullFilePath), 'r');
        if ($handle) {
            while (!feof($handle)) {
                $content = fgets($handle);
                
                if(strlen(trim($content)) <= 1)
                {
                    return false;
                }

                $lineCount++;
                if ($lineCount > 1) {
                    break;
                }
            }
            fclose($handle);
        }

        return $lineCount > 1;
    }

    public function zip($filename = null)
    {
        if(!$this->checkIfFileIsNonempty())
        {
            self::$storage->delete($this->fullFilePath);
            return null;
        }

        if (!$this->fullFilePath || !self::$storage->exists($this->fullFilePath)) {
            trigger_error('File is not initialized or does not exist.', E_USER_WARNING);
            return null;
        }

        // $filename = $filename ?? $this->filename;
        $target = $this->getZipFilePath();
        try {
            $zip = new \ZipArchive();
            if ($zip->open(self::$storage->path($target), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                if (!$zip->addFile(self::$storage->path($this->fullFilePath), basename($this->fullFilePath))) {
                    trigger_error('Failed to add file to zip.', E_USER_WARNING);
                }
                $zip->close();
            } else {
                trigger_error('Failed to create zip archive.', E_USER_WARNING);
            }
        } catch (\Throwable $e) {
            trigger_error('Error creating zip: ' . $e->getMessage(), E_USER_WARNING);
        }

        return $this;
    }

    public function keepLastChanged()
    {
        // Compare with the last dump and check hash. If the same, remove the old one.
        $current = self::$storage->path($this->getZipFilePath());

        if(!file_exists($current))
        {
            return $this;
        }

        $existing = glob($this->getTargetFolder() . '/*.zip');

        if(empty($existing))
        {
            return $this;
        }

        rsort($existing);

        foreach($existing as $zip)
        {
            if($zip === $current)
            {
                continue;
            }

            $zipExisting = new \ZipArchive();
            if($zipExisting->open($zip) === true)
            {
                $lastContent = $zipExisting->getFromIndex(0);
                $zipExisting->close();

                if(md5($lastContent) === md5_file($this->fullFilePath))
                {
                    unlink($zip);
                }
            }

            break;
        }

        return $this;
    }

    public function deleteFile()
    {
        if ($this->fullFilePath && self::$storage->exists($this->fullFilePath)) 
        {
            try {
                self::$storage->delete($this->fullFilePath);
            } catch (\Throwable $e) {
                trigger_error('Error deleting file: ' . $e->getMessage(), E_USER_WARNING);
            }
        }

        return $this;
    }
}


class ExportFileColumn
{
    function __construct(
        public string $key,
        public string $label
    ){}

    public function getValue(array | object $data)
    {
        return data_get($data, $this->key);
    }

    public static function make(string $key, ?string $label = null)
    {
        return new self($key, $label ?? ucfirst(preg_replace('/^.*\./', '', $key)));
    }
}

class ExportFileHeader
{
    /**
     * @var ExportFileColumn[]
     */
    public $items = [];

    public static function make() : self
    {
        return new self();
    }

    public function as_array() : array
    {
        return array_map( function($item) { return $item->label; }, $this->items);
    }

    public function structure($prefix = '')
    {
        $prefix = $prefix ? $prefix . '.' : '';

        $this->items = [
            ...$this->items,
            ExportFileColumn::make($prefix.'identifier'),
            ExportFileColumn::make($prefix.'name'),
            ExportFileColumn::make($prefix.'canonical_smiles'),
            ExportFileColumn::make($prefix.'inchikey'),
            ExportFileColumn::make($prefix.'mw'),
            ExportFileColumn::make($prefix.'logp'),
            ExportFileColumn::make($prefix.'pubchem'),
            ExportFileColumn::make($prefix.'pdb'),
            ExportFileColumn::make($prefix.'chembl'),
            ExportFileColumn::make($prefix.'chebi'),
            ExportFileColumn::make($prefix.'drugbank')
        ];

        return $this;
    }

    public function passiveInteraction($prefix = '')
    {
        $prefix = $prefix ? $prefix . '.' : '';

        $this->items = [
            ...$this->items,
            ExportFileColumn::make($prefix.'membrane', 'Membrane'),
            ExportFileColumn::make($prefix.'method', 'Method'),
            ExportFileColumn::make($prefix.'temperature'),
            ExportFileColumn::make($prefix.'ph'),
            ExportFileColumn::make($prefix.'charge'),
            ExportFileColumn::make($prefix.'note'),
            ExportFileColumn::make($prefix.'x_min', 'Xmin'),
            ExportFileColumn::make($prefix.'x_min_accuracy', '+/- Xmin'),
            ExportFileColumn::make($prefix.'gpen', 'Gpen'),
            ExportFileColumn::make($prefix.'gpen_accuracy', '+/- Gpen'),
            ExportFileColumn::make($prefix.'gwat', 'Gwat'),
            ExportFileColumn::make($prefix.'gwat_accuracy', '+/- Gwat'),
            ExportFileColumn::make($prefix.'logk', 'LogK'),
            ExportFileColumn::make($prefix.'logk_accuracy', '+/- LogK'),
            ExportFileColumn::make($prefix.'logperm', 'LogPerm'),
            ExportFileColumn::make($prefix.'logperm_accuracy', '+/- LogPerm'),
            ExportFileColumn::make($prefix.'primary_citation', 'Primary referece'),
            ExportFileColumn::make($prefix.'secondary_citation', 'Secondary referece'),
        ];

        return $this;
    }

    public function activeInteraction($prefix = '')
    {
        $prefix = $prefix ? $prefix . '.' : '';

        $this->items = [
            ...$this->items,
            ExportFileColumn::make($prefix.'protein', 'Protein'),
            ExportFileColumn::make($prefix.'temperature'),
            ExportFileColumn::make($prefix.'ph'),
            ExportFileColumn::make($prefix.'charge'),
            ExportFileColumn::make($prefix.'note'),
            ExportFileColumn::make($prefix.'km', 'Km'),
            ExportFileColumn::make($prefix.'km_accuracy', '+/- Km'),
            ExportFileColumn::make($prefix.'ec50', 'EC50'),
            ExportFileColumn::make($prefix.'ec50_accuracy', '+/- EC50'),
            ExportFileColumn::make($prefix.'ki', 'Ki'),
            ExportFileColumn::make($prefix.'ki_accuracy', '+/- Ki'),
            ExportFileColumn::make($prefix.'ic50', 'IC50'),
            ExportFileColumn::make($prefix.'ic50_accuracy', '+/- IC50'),
            ExportFileColumn::make($prefix.'primary_citation', 'Primary referece'),
            ExportFileColumn::make($prefix.'secondary_citation', 'Secondary referece'),
        ];

        return $this;
    }
}