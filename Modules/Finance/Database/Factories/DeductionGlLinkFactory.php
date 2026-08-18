<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\DeductionGlLink;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * @extends Factory<DeductionGlLink>
 */
class DeductionGlLinkFactory extends Factory
{
    protected $model = DeductionGlLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deduction_category_id' => null,
            'deduction_id' => null,
            'account_id' => Account::factory(),
            'is_active' => true,
        ];
    }
}
