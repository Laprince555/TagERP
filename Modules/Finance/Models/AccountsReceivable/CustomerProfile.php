<?php

namespace Modules\Finance\Models\AccountsReceivable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\Models\Customers\Customer;
use Modules\Finance\Database\Factories\CustomerProfileFactory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;

/**
 * A customer's financial identity for AR: the currency, control account, and
 * credit terms an invoice must route through. A Customer can carry several
 * profiles (e.g. one for retail, one for wholesale); an invoice always
 * posts against a specific profile, never the bare Customer.
 *
 * @property string $code
 * @property string $slug
 * @property CustomerFinancialRole $financial_role
 */
class CustomerProfile extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-ar-cpr';

    protected $table = 'finance_ar_customer_profiles';

    protected $fillable = [
        'customer_id',
        'financial_role',
        'currency_id',
        'ar_account_id',
        'credit_terms_days',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'financial_role' => CustomerFinancialRole::class,
            'credit_terms_days' => 'integer',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ar_account_id');
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerProfile $profile): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($profile->slug)) {
                $role = $profile->financial_role instanceof CustomerFinancialRole ? $profile->financial_role->value : (string) $profile->financial_role;
                $base = ($profile->customer?->company?->name ?? (string) $profile->customer_id).'-'.$role;
                $profile->slug = $builder->uniqueSlug($base, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($profile->code)) {
                $profile->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $profile->slug);
            }
        });
    }

    protected static function newFactory(): CustomerProfileFactory
    {
        return CustomerProfileFactory::new();
    }
}
