<?php

namespace Modules\Finance\Models\CashAndBanks\PaymentDisburse;

use App\Support\Code\HasAutoLineCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\PaymentDisbursementDetailFactory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\System\SubApplication;
use Modules\HR\Models\Employee;

/**
 * @property int $line_number
 */
class PaymentDisbursementDetail extends Model
{
    use HasAutoLineCode, HasFactory;

    protected $fillable = [
        'code',
        'payment_disbursement_request_id',
        'sub_application_id',
        'line_number',
        'payee_type',
        'payee_id',
        'amount',
        'gl_account_id',
        'description',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'line_number' => 'integer',
    ];

    /**
     * Registered before parent::boot() so line_number is assigned before
     * HasAutoLineCode's own creating() listener (registered inside
     * parent::boot()'s bootTraits() call) reads it to build the code.
     */
    protected static function boot()
    {
        static::creating(function (self $model) {
            if (blank($model->line_number)) {
                $model->line_number = ((int) static::query()
                    ->where('payment_disbursement_request_id', $model->payment_disbursement_request_id)
                    ->max('line_number')) + 1;
            }
        });

        parent::boot();
    }

    protected function lineParentRelationName(): string
    {
        return 'paymentDisbursementRequest';
    }

    protected function lineCodeSlug(): string
    {
        return 'det';
    }

    public function paymentDisbursementRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentDisbursementRequest::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'payee_id')->where('payee_type', 'employee');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function subApplication(): BelongsTo
    {
        return $this->belongsTo(SubApplication::class);
    }

    protected static function newFactory()
    {
        return PaymentDisbursementDetailFactory::new();
    }
}
