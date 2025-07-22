<?php
namespace App\Enums;

enum UploadQueueLogContextEnums: string
{
    case ERROR = "error";
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case INFO = 'info';
}