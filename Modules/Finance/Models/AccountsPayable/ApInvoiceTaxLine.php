<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\HasAutoLineCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\ApInvoiceTaxLineFactory;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\Tax\Tax;
use Modules\General\System\SubApplication;

/**
 * One tax line on an AP invoice — a sub-application of ApInvoice, separate
 * from the item lines and deduction lines.
 *
 * @property string $code
 * @property int $line_number
 */
class ApInvoiceTaxLine extends Model
{
    use HasAutoLineCode, HasFactory;

    protected $table = 'finance_ap_invoice_tax_lines';

    protected $fillable = [
        'invoice_id',
        'sub_application_id',
        'line_number',
        'tax_id',
        'cost_center_id',
        'taxable_amount',
        'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'taxable_amount' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'line_number' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ApInvoice::class, 'invoice_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function subApplication(): BelongsTo
    {
        return $this->belongsTo(SubApplication::class);
    }

    /**
     * Registered before parent::boot() so line_number is assigned before
     * HasAutoLineCode's own creating() listener (registered inside
     * parent::boot()'s bootTraits() call) reads it to build the code.
     */
    protected static function boot(): void
    {
        static::creating(function (ApInvoiceTaxLine $line): void {
            if (blank($line->line_number)) {
                $line->line_number = ((int) static::query()
                    ->where('invoice_id', $line->invoice_id)
                    ->max('line_number')) + 1;
            }
        });

        parent::boot();
    }

    protected function lineParentRelationName(): string
    {
        return 'invoice';
    }

    protected function lineCodeSlug(): string
    {
        return 'txl';
    }

    protected static function newFactory(): ApInvoiceTaxLineFactory
    {
        return ApInvoiceTaxLineFactory::new();
    }
}
