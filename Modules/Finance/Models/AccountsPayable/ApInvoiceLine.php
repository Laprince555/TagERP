<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\HasAutoLineCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\ApInvoiceLineFactory;
use Modules\General\System\SubApplication;
use RuntimeException;

/**
 * One line of one AP invoice. `line_total` is always derived from
 * `quantity * unit_price` rather than entered, so a line can never disagree
 * with its own inputs.
 *
 * @property string $code
 * @property int $line_number
 */
class ApInvoiceLine extends Model
{
    use HasAutoLineCode, HasFactory;

    protected $table = 'finance_ap_invoice_lines';

    protected $fillable = [
        'invoice_id',
        'sub_application_id',
        'line_number',
        'description',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'line_total' => 'decimal:6',
            'line_number' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ApInvoice::class, 'invoice_id');
    }

    public function subApplication(): BelongsTo
    {
        return $this->belongsTo(SubApplication::class);
    }

    protected static function booted(): void
    {
        static::creating(function (ApInvoiceLine $line): void {
            if (blank($line->line_number)) {
                $line->line_number = ((int) static::query()
                    ->where('invoice_id', $line->invoice_id)
                    ->max('line_number')) + 1;
            }
        });

        static::saving(function (ApInvoiceLine $line): void {
            if ((float) $line->quantity <= 0) {
                throw new RuntimeException('An invoice line must carry a positive quantity.');
            }

            if ((float) $line->unit_price < 0) {
                throw new RuntimeException('An invoice line cannot carry a negative unit price.');
            }

            $line->line_total = bcmul((string) $line->quantity, (string) $line->unit_price, 6);
        });
    }

    protected function lineParentRelationName(): string
    {
        return 'invoice';
    }

    protected function lineCodeSlug(): string
    {
        return 'lin';
    }

    protected static function newFactory(): ApInvoiceLineFactory
    {
        return ApInvoiceLineFactory::new();
    }
}
