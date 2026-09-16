<?php

namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnXminAcc extends ColumnAccuracy
{
    public static string $key = 'x_min_acc';

    public static string $label = '+/- Xmin';

    public static string $databaseColumn = 'x_min_accuracy';
}
