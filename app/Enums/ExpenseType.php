<?php

namespace App\Enums;

enum ExpenseType: string
{
    case Fuel = 'Fuel';
    case Tolls = 'Tolls';
    case Repairs = 'Repairs';
    case Fines = 'Fines';
    case Other = 'Other';
}
