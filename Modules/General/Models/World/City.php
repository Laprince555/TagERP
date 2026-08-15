<?php

namespace Modules\General\Models\World;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\People\Person;
use Nnjeim\World\Models\City as BaseCity;

class City extends BaseCity
{
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }
}
