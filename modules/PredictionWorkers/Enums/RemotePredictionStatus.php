<?php

namespace Modules\PredictionWorkers\Enums;

enum RemotePredictionStatus: string
{
    case PENDING = 'pending';
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case WAITING_FOR_CONFORMERS = 'waiting_for_conformers';
    case WAITING_FOR_SCRIPT = 'waiting_for_script';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
