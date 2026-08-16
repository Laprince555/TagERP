<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Models\GeneralLedger\LedgerConversionType;
use Modules\Finance\Models\GeneralLedger\RateType;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * @extends Factory<Ledger>
 */
class LedgerFactory extends Factory
{
    protected $model = Ledger::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'entity_id' => Entity::factory(),
            'chart_id' => Chart::factory(),
            'base_currency_id' => Currency::factory(),
            'is_primary' => true,
            'primary_ledger_id' => null,
            'conversion_type' => null,
            'rate_type' => RateType::Daily,
            'rounding_account_id' => null,
            'is_active' => true,
        ];
    }

    public function secondaryOf(Ledger $primary, LedgerConversionType $type = LedgerConversionType::Currency): static
    {
        return $this->state(fn (): array => [
            'entity_id' => $primary->entity_id,
            'is_primary' => false,
            'primary_ledger_id' => $primary->getKey(),
            'conversion_type' => $type,
            'rounding_account_id' => $type->convertsCurrency() ? Account::factory() : null,
        ]);
    }
}
