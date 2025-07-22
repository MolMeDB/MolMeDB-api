<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnGwatAcc extends ColumnAccuracy
{
    public static string $key = 'g_wat_acc';
    public static string $label = '+/- Gwat';
}