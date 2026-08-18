<?php

namespace Modules\Finance\Models\CashAndBanks\Collection;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\CollectionRequestFactory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\HR\Models\OrganizationStructure\Entity;

class CollectionRequest extends Model
{
    use HasFactory, SoftDeletes;

    const APPLICATION_CODE = 'fin-cbn-col';

    protected $fillable = [
        'code',
        'slug',
        'number',
        'entity_id',
        'collection_date',
        'expected_date',
        'amount',
        'currency_id',
        'description',
        'status',
        'posted_at',
        'journal_id',
        'collection_method',
        'to_bank_id',
        'to_safe_id',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'expected_date' => 'date',
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

    public function toBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'to_bank_id');
    }

    public function toSafe(): BelongsTo
    {
        return $this->belongsTo(Safe::class, 'to_safe_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(CollectionDetail::class);
    }

    protected static function newFactory()
    {
        return CollectionRequestFactory::new();
    }
}
