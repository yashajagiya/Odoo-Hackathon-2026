<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Available = 'Available';
    case OnTrip = 'On Trip';
    case OffDuty = 'Off Duty';
    case Suspended = 'Suspended';
}
