<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * @extends Factory<ApInvoice>
 */
class ApInvoiceFactory extends Factory
{
    protected $model = ApInvoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_profile_id' => VendorProfile::factory(),
            'entity_id' => Entity::factory(),
            'branch_id' => fn (array $attributes) => Branch::factory()->create(['entity_id' => $attributes['entity_id']])->id,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'issue_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'due_date' => null,
            'currency_id' => Currency::factory(),
            'po_reference' => null,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
        ];
    }

    /** Matches the invoice's currency to the given (or a fresh) vendor profile's currency. */
    public function forVendorProfile(VendorProfile $profile): static
    {
        return $this->state(fn (): array => [
            'vendor_profile_id' => $profile->id,
            'currency_id' => $profile->currency_id,
        ]);
    }
}
