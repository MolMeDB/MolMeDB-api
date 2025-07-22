<?php
namespace App\Rules\UploadFile\ActiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnKiAcc extends ColumnAccuracy
{
    public static string $key = 'ki_acc';
    public static string $label = '+/- Ki';
}