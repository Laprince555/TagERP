<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\ApInvoiceTypeFactory;

/**
 * A user-defined classification of an AP invoice's content (material,
 * service, untaxed invoice, ...), distinct from DocumentType (invoice,
 * credit note, debit note). An invoice with no type set is "general" and
 * is intentionally excluded from type-based cycle matching.
 *
 * @property string $code
 * @property string $name
 */
class ApInvoiceType extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-ap-ivt';

    protected $table = 'finance_ap_invoice_types';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ApInvoice::class, 'invoice_type_id');
    }

    protected static function booted(): void
    {
        static::creating(function (ApInvoiceType $type): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($type->slug)) {
                $type->slug = $builder->uniqueSlug($type->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($type->code)) {
                $type->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $type->slug);
            }
        });
    }

    protected static function newFactory(): ApInvoiceTypeFactory
    {
        return ApInvoiceTypeFactory::new();
    }
}
