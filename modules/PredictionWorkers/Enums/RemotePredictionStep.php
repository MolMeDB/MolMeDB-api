<?php

namespace Modules\PredictionWorkers\Enums;

enum RemotePredictionStep: string
{
    case RDKIT = 'rdkit';
    case CONFORMERS = 'conformers';
    case OPTIMIZATION_ORCA = 'optimization-orca';
    case OPTIMIZATION_TURBOMOLE = 'optimization-turbomole';
    case COSMO = 'cosmo';
}
