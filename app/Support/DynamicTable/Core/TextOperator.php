<?php

namespace App\Support\DynamicTable\Core;

enum TextOperator: string
{
    case Contains = 'contains';
    case Equals = 'equals';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case DoesNotContain = 'does_not_contain';
    case DoesNotEqual = 'does_not_equal';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
}
