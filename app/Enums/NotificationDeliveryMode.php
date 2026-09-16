<?php

namespace App\Enums;

enum NotificationDeliveryMode: string
{
    case IMMEDIATE = 'immediate';
    case BATCHED = 'batched';
}
