<?php

namespace Modules\PredictionWorkers\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Prediction extends PredictionBaseModel
{
    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;

    /** STATES */
    const STATE_STOPPED = 0;
    const STATE_PREPARED = 1;
    const STATE_ERROR = 2;
    const STATE_REMOVE = 3;
    const STATE_RUNNING = 4;
    const STATE_FINISHED = 4;

    /** COSMO STEPS */
    const STEP_PENDING = 0;
    const STEP_IONIZED = 1;
    const STEP_SDF_READY = 2;
    const STEP_OPTIMIZATION = 3;
    const STEP_COSMO = 4;
    const STEP_RESULT_DOWNLOAD = 5;
    const STEP_RESULT_PARSE = 6;
    const STEP_RESULT_DB_STORE = 7;

    /** METHODS */
    const METHOD_COSMOPERM = 'cosmoperm';
    const METHOD_COSMOMIC = 'cosmomic';

    public static $enum_methods = [
        self::METHOD_COSMOMIC => 'CosmoMic',
        self::METHOD_COSMOPERM => 'CosmoPerm',
    ];

    public static $enum_method_shorts = [
        self::METHOD_COSMOMIC => 'mic',
        self::METHOD_COSMOPERM => 'perm',
    ];

    public static $enum_steps = [
        self::STEP_PENDING => 'Pending',
        self::STEP_IONIZED => 'Ionized',
        self::STEP_SDF_READY => 'SDF Ready',
        self::STEP_OPTIMIZATION => 'Optimization Running',
        self::STEP_COSMO => 'COSMO Running',
        self::STEP_RESULT_DOWNLOAD => 'Result prepared for parsing',
        self::STEP_RESULT_PARSE => 'Result Parsed',
        self::STEP_RESULT_DB_STORE => 'Result Stored'
    ];

    public static $enum_states = [
        self::STATE_STOPPED => 'Stopped',
        self::STATE_PREPARED => 'Prepared',
        self::STATE_RUNNING => 'Running',
        self::STATE_FINISHED => 'Finished',
        self::STATE_ERROR => 'Error',
        self::STATE_REMOVE => 'Remove'
    ];

    public static $enum_priorities = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_MEDIUM => 'Medium',
        self::PRIORITY_HIGH => 'High'
    ];

    public static function enumMethod($method) : string
    {
        return self::$enum_methods[$method];
    }

    public static function enumStep($step) : string
    {
        return self::$enum_steps[$step];
    }

    public static function enumState($state) : string
    {
        return self::$enum_states[$state];
    }

    public static function enumPriority($priority) : string
    {
        return self::$enum_priorities[$priority];
    }

    public function predictionDatasets() : BelongsToMany
    {
        return $this->belongsToMany(PredictionDataset::class, 'prediction_has_datasets', 'prediction_id', 'dataset_id')
            ->withTimestamps();
    }

    public function predictionResult(): BelongsTo
    {
        return $this->belongsTo(PredictionResult::class, 'result_id');
    }

    public function predictionStructure() : BelongsTo
    {
        return $this->belongsTo(PredictionStructure::class, 'structure_id');
    }

    public function predictionMembrane() : BelongsTo
    {
        return $this->belongsTo(PredictionMembrane::class, 'membrane_id');
    }
}
