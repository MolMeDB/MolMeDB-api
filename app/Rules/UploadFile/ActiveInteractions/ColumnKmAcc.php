<?php
namespace App\Rules\UploadFile\ActiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnKmAcc extends ColumnAccuracy
{
    public static string $key = 'km_acc';
    public static string $label = '+/- Km';
}