<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnAccuracy;

class ColumnGpenAcc extends ColumnAccuracy
{
    public static string $key = 'g_pen_acc';
    public static string $label = '+/- Gpen';
}