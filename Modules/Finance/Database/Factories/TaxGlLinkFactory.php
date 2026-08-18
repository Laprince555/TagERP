<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\Tax\TaxGlLink;

/**
 * @extends Factory<TaxGlLink>
 */
class TaxGlLinkFactory extends Factory
{
    protected $model = TaxGlLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tax_category_id' => null,
            'tax_id' => null,
            'account_id' => Account::factory(),
            'is_active' => true,
        ];
    }
}
