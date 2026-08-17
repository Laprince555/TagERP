<?php

namespace Modules\General\Models\World;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\General\Models\World\People\Person;

class Country extends \Nnjeim\World\Models\Country
{
    public function persons(): HasManyThrough
    {
        return $this->hasManyThrough(Person::class, City::class);
    }
}
