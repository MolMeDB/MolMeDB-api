<?php
namespace App\Rules\UploadFile\ActiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnEc50Acc extends ColumnAccuracy
{
    public static string $key = 'ec50_acc';
    public static string $label = '+/- Ec50';
}