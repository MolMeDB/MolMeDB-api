<?php

namespace Modules\PredictionWorkers\Enums;

enum RemotePredictionArtifact: string
{
    case SDF = 'sdf';
    case CONFORMERS = 'conformers';
    case COSMO = 'cosmo';
}
