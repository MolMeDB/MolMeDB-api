<?php

namespace App\Rules\UploadFile\ActiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnIc50Acc extends ColumnAccuracy
{
    public static string $key = 'ic50_acc';

    public static string $label = '+/- Ic50';

    public static string $databaseColumn = 'ic50_accuracy';
}
