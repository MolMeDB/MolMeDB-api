<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnLogKAcc extends ColumnAccuracy
{
    public static string $key = 'log_k_acc';
    public static string $label = '+/- LogK';
}