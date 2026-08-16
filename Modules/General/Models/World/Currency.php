<?php

namespace Modules\General\Models\World;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\General\Database\Factories\CurrencyFactory;
use Nnjeim\World\Models\Currency as BaseCurrency;

class Currency extends BaseCurrency
{
    use HasFactory;

    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }
}
