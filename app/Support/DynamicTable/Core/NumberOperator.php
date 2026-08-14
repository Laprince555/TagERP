<?php

namespace App\Support\DynamicTable\Core;

enum NumberOperator: string
{
    case Equals = 'equals';
    case DoesNotEqual = 'does_not_equal';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case Between = 'between';
    case NotBetween = 'not_between';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
}
