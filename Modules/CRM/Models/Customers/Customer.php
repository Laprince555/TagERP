<?php

namespace Modules\CRM\Models\Customers;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\Database\Factories\CustomerFactory;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;

/**
 * A Company acting as a customer. This is the legal/classification record —
 * financial routing (currency, ledger account, credit terms) lives on the
 * Finance/AR Customer Profile(s) that reference this customer instead, since one
 * customer can carry several financial roles.
 *
 * @property string $code
 * @property string $slug
 * @property CustomerType $customer_type
 */
class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'crm-cust-cus';

    protected $table = 'crm_customers';

    protected $fillable = [
        'company_id',
        'customer_category_id',
        'customer_type',
        'default_currency_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($customer->slug)) {
                $base = $customer->company?->name ?? (string) $customer->company_id;
                $customer->slug = $builder->uniqueSlug($base, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($customer->code)) {
                $customer->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $customer->slug);
            }
        });
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
