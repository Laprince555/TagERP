<?php

namespace Modules\Procurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\Models\World\Companies\Company;
use Modules\Procurement\Models\Vendors\Vendor;
use Modules\Procurement\Models\Vendors\VendorType;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'vendor_type' => fake()->randomElement(VendorType::cases())->value,
            'default_currency_id' => null,
            'requires_po' => false,
            'is_active' => true,
        ];
    }
}
