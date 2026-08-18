<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\VendorProfileFactory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;
use Modules\Procurement\Models\Vendors\Vendor;

/**
 * A vendor's financial identity for AP: the currency, control account, and
 * payment terms an invoice must route through. A Vendor can carry several
 * profiles (e.g. one for materials, one for services); an invoice always
 * posts against a specific profile, never the bare Vendor.
 *
 * @property string $code
 * @property string $slug
 * @property VendorFinancialRole $financial_role
 */
class VendorProfile extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-ap-vpr';

    protected $table = 'finance_ap_vendor_profiles';

    protected $fillable = [
        'vendor_id',
        'financial_role',
        'currency_id',
        'ap_account_id',
        'payment_terms_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'financial_role' => VendorFinancialRole::class,
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ap_account_id');
    }

    protected static function booted(): void
    {
        static::creating(function (VendorProfile $profile): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($profile->slug)) {
                $role = $profile->financial_role instanceof VendorFinancialRole ? $profile->financial_role->value : (string) $profile->financial_role;
                $base = ($profile->vendor?->company?->name ?? (string) $profile->vendor_id).'-'.$role;
                $profile->slug = $builder->uniqueSlug($base, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($profile->code)) {
                $profile->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $profile->slug);
            }
        });
    }

    protected static function newFactory(): VendorProfileFactory
    {
        return VendorProfileFactory::new();
    }
}
