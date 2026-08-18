<?php

namespace Modules\Finance\Models\CashAndBanks\PaymentDisburse;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\PaymentDisbursementRequestFactory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\HR\Models\OrganizationStructure\Entity;

class PaymentDisbursementRequest extends Model
{
    use HasFactory, SoftDeletes;

    const APPLICATION_CODE = 'fin-cbn-pdr';

    protected $fillable = [
        'code',
        'slug',
        'number',
        'entity_id',
        'payment_date',
        'amount',
        'currency_id',
        'description',
        'status',
        'posted_at',
        'journal_id',
        'payment_method',
        'from_bank_id',
        'from_safe_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'posted_at' => 'datetime',
        'amount' => 'decimal:6',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $codeBuilder = app(RecordCodeBuilder::class);
            $model->code = $codeBuilder->applicationRecordCode(self::APPLICATION_CODE, $model->slug);
        });
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function fromBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'from_bank_id');
    }

    public function fromSafe(): BelongsTo
    {
        return $this->belongsTo(Safe::class, 'from_safe_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PaymentDisbursementDetail::class);
    }

    protected static function newFactory()
    {
        return PaymentDisbursementRequestFactory::new();
    }
}
