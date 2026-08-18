<?php

namespace Modules\Procurement\Models\Vendors;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;
use Modules\Procurement\Database\Factories\VendorFactory;

/**
 * A Company acting as a vendor. This is the legal/classification record —
 * financial routing (currency, ledger account, payment terms) lives on the
 * Finance/AP Vendor Profile(s) that reference this vendor instead, since one
 * vendor can carry several financial roles.
 *
 * @property string $code
 * @property string $slug
 * @property VendorType $vendor_type
 */
class Vendor extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'proc-ven-vnd';

    protected $table = 'procurement_vendors';

    protected $fillable = [
        'company_id',
        'vendor_type',
        'default_currency_id',
        'requires_po',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'vendor_type' => VendorType::class,
            'requires_po' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($vendor->slug)) {
                $base = $vendor->company?->name ?? (string) $vendor->company_id;
                $vendor->slug = $builder->uniqueSlug($base, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($vendor->code)) {
                $vendor->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $vendor->slug);
            }
        });
    }

    protected static function newFactory(): VendorFactory
    {
        return VendorFactory::new();
    }
}
