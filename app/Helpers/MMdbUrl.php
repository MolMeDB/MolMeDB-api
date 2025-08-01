<?php
namespace App\Helpers;

use App\Models\Structure;

class MMdbUrl {
    public static function structure3D(Structure $structure)
    {
        if(!$structure->identifier)
        {
            return null;
        }

        return url("/api/structure/mol/3d/{$structure->identifier}");
    }
}