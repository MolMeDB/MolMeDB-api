<?php 
namespace Modules\PredictionWorkers\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionBaseModel extends Model
{
    protected $connection;
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('database.default_predictions');
    }
}