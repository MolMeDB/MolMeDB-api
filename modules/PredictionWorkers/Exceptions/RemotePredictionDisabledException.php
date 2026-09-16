<?php

namespace Modules\PredictionWorkers\Exceptions;

use RuntimeException;

class RemotePredictionDisabledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Remote prediction service integration is disabled.');
    }
}
