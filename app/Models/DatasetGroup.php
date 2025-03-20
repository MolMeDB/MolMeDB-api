<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatasetGroup extends Model
{
    use HasFactory;

    public function datasets() : HasMany
    {
        return $this->hasMany(Dataset::class);
    }
}
