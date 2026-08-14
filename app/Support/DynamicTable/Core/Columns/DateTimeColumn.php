<?php

namespace App\Support\DynamicTable\Core\Columns;

class DateTimeColumn extends DateColumn
{
    protected string $format = 'Y-m-d H:i';
}
