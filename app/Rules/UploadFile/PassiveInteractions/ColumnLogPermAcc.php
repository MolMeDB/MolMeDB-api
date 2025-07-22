<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnLogPermAcc extends ColumnAccuracy
{
    public static string $key = 'log_perm_acc';
    public static string $label = '+/- LogPerm';
}