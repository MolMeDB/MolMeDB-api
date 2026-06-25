<?php

namespace App\Enums;

enum UploadQueueLogTypeEnums: string
{
    case UPLOAD = 'UPLOAD';
    case STATE_CHANGE = 'STATE CHANGE';
    case VALIDATION_RUN = 'VALIDATION RUN';
    case REUPLOAD = 'REUPLOAD';
    case UPLOAD_RUN = 'UPLOAD RUN';
    case NOTIFICATION = 'NOTIFICATION';
}
