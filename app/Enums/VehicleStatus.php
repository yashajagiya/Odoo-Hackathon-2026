<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'Available';
    case OnTrip = 'On Trip';
    case InShop = 'In Shop';
    case Retired = 'Retired';
}
