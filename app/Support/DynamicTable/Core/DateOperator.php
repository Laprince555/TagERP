<?php

namespace App\Support\DynamicTable\Core;

enum DateOperator: string
{
    case On = 'on';
    case Before = 'before';
    case BeforeOrOn = 'before_or_on';
    case After = 'after';
    case AfterOrOn = 'after_or_on';
    case Between = 'between';
    case NotBetween = 'not_between';
    case Today = 'today';
    case Yesterday = 'yesterday';
    case ThisWeek = 'this_week';
    case ThisMonth = 'this_month';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
}
