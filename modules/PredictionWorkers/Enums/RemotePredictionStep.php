<?php

namespace Modules\PredictionWorkers\Enums;

enum RemotePredictionStep: string
{
    case RDKIT = 'rdkit';
    case CONFORMERS = 'conformers';
    case OPTIMIZATION_ORCA = 'optimization-orca';
    case OPTIMIZATION_TURBOMOLE = 'optimization-turbomole';
    case COSMO = 'cosmo';

    public function label(): string
    {
        return match ($this) {
            self::RDKIT => 'RDKit',
            self::CONFORMERS => 'Conformers',
            self::OPTIMIZATION_ORCA => 'Structure optimization (ORCA)',
            self::OPTIMIZATION_TURBOMOLE => 'Structure optimization (Turbomole)',
            self::COSMO => 'COSMO calculation',
        };
    }
}
