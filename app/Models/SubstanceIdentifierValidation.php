<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubstanceIdentifierValidation extends Model
{
    /** @use HasFactory<\Database\Factories\SubstanceIdentifierValidationFactory> */
    use HasFactory;

    /** STATES */
    const STATE_NEW = 0;
    const STATE_VALIDATED = 1;
    const STATE_INVALID = 2;
}
