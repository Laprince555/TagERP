<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Models\Concerns\ScopedToOrganization;
use App\Models\User;
use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Database\Factories\ApInvoiceFactory;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Entity;
use Modules\HR\Support\Cycles\HasCycleTransactions;
use RuntimeException;

/**
 * One vendor invoice header, routed to a specific Vendor Financial Profile
 * rather than the bare vendor (see VendorProfile). Immutable once it leaves
 * Draft/Rejected — see the guards in booted() — because every step past
 * that point is a decision (submit, approve, reject, activate) that
 * ApInvoiceApprover owns, not a field edit.
 *
 * @property string $code
 * @property ApInvoiceStatus $status
 */
class ApInvoice extends Model
{
    use HasCycleTransactions;
    use HasFactory;
    use ScopedToOrganization;

    public const APPLICATION_CODE = 'fin-ap-inv';

    protected $table = 'finance_ap_invoices';

    protected $fillable = [
        'vendor_profile_id',
        'entity_id',
        'branch_id',
        'invoice_type_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'currency_id',
        'exchange_rate',
        'po_reference',
        'subtotal',
        'tax_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'immutable_date',
            'due_date' => 'immutable_date',
            'exchange_rate' => 'decimal:10',
            'subtotal' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'status' => ApInvoiceStatus::class,
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
        ];
    }

    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function invoiceType(): BelongsTo
    {
        return $this->belongsTo(ApInvoiceType::class, 'invoice_type_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ApInvoiceLine::class, 'invoice_id')->orderBy('line_number');
    }

    public function taxLines(): HasMany
    {
        return $this->hasMany(ApInvoiceTaxLine::class, 'invoice_id');
    }

    public function deductionLines(): HasMany
    {
        return $this->hasMany(ApInvoiceDeductionLine::class, 'invoice_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    protected static function booted(): void
    {
        static::creating(function (ApInvoice $invoice): void {
            if (blank($invoice->entity_id) || blank($invoice->branch_id)) {
                throw new RuntimeException('An AP invoice must be created with an entity_id and branch_id so it can be scoped to a company/branch.');
            }

            $builder = app(RecordCodeBuilder::class);

            if (blank($invoice->slug)) {
                $invoice->slug = $builder->uniqueSlug(
                    'inv-'.$invoice->invoice_number,
                    fn (string $slug): bool => static::where('slug', $slug)->exists(),
                );
            }

            if (blank($invoice->code)) {
                $invoice->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $invoice->slug);
            }
        });

        // Once an invoice has left Draft/Rejected it is somebody's decision in
        // progress; only ApInvoiceApprover's status/actor/timestamp columns
        // may still change, exactly as Journal locks down after posting.
        static::updating(function (ApInvoice $invoice): void {
            $original = $invoice->getOriginal('status');

            $originalStatus = match (true) {
                $original instanceof ApInvoiceStatus => $original,
                blank($original) => ApInvoiceStatus::Draft,
                default => ApInvoiceStatus::from((string) $original),
            };

            if ($originalStatus->isEditable()) {
                return;
            }

            $changed = array_keys($invoice->getDirty());
            $allowed = [
                'status', 'updated_at',
                'submitted_at', 'submitted_by',
                'approved_at', 'approved_by',
                'rejected_at', 'rejected_by', 'rejection_reason',
                'activated_at', 'activated_by',
            ];

            if (array_diff($changed, $allowed) !== []) {
                throw new RuntimeException("Invoice [{$invoice->code}] is {$originalStatus->label()} and cannot be modified.");
            }
        });

        static::deleting(function (ApInvoice $invoice): void {
            if (! $invoice->status->isEditable()) {
                throw new RuntimeException("Invoice [{$invoice->code}] is {$invoice->status->label()} and cannot be deleted.");
            }
        });
    }

    protected static function newFactory(): ApInvoiceFactory
    {
        return ApInvoiceFactory::new();
    }
}
