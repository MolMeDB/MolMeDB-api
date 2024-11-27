<?php

namespace App\Enums;

enum IconEnums: string
{
    case ACCESS = 'heroicon-o-key';
    case CATEGORIES = 'heroicon-o-squares-2x2';
    case DOWNLOAD = 'heroicon-s-arrow-down-tray';
    case METHOD = 'heroicon-o-beaker';
    case MEMBRANE = 'heroicon-o-circle-stack';
    case PERMISSIONS = 'heroicon-o-bars-3';
    case PUBLICATIONS = 'heroicon-o-paper-clip';
    case QUESTION_MARK = 'heroicon-o-question-mark-circle';
    case RELOAD = 'heroicon-s-arrow-path';
    case ROLES = 'heroicon-o-tag';
    case STATE_NEW = 'heroicon-o-lock-open';
    case STATE_VALIDATED = 'heroicon-o-lock-closed';
    case STATE_INVALID = 'heroicon-o-exclamation-triangle';
    case USERS = 'heroicon-o-user-group';
}