<?php

namespace App\Enums;

enum TripStatus: string
{
    case Draft = 'Draft';
    case Dispatched = 'Dispatched';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
