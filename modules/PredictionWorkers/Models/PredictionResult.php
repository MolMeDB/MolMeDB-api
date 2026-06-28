<?php

namespace Modules\PredictionWorkers\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\PredictionWorkers\Services\CosmoXmlParser;

class PredictionResult extends PredictionBaseModel
{
    protected $connection = 'predictions';

    protected $table = 'results';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function prediction(): HasOne
    {
        return $this->hasOne(Prediction::class, 'result_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PredictionFile::class, 'file_id');
    }

    public function loadParsedResults()
    {
        try {
            if ($this->data !== null) {
                return $this->data;
            }

            $parser = new CosmoXmlParser;

            if (! $this->file) {
                return null;
            }

            return $parser->parse($this->file);
        } catch (\Exception $e) {
            return false;
        }
    }
}
