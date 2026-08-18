<?php

namespace Modules\Finance\Models\CashAndBanks\Banks;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Database\Factories\BankFactory;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * A bank account holder within a specific entity. Each bank is scoped to one entity
 * and has a default GL account for cash posting.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property int $entity_id
 * @property int $category_id
 * @property ?int $default_gl_account_id
 */
class Bank extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'fin-cbn-bnk';

    protected $table = 'banks';

    protected $fillable = [
        'name',
        'entity_id',
        'category_id',
        'default_gl_account_id',
        'bank_code',
        'bank_name',
        'swift_code',
        'iban',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BankCategory::class, 'category_id');
    }

    public function defaultGlAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_gl_account_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'bank_id');
    }

    public function checksBooks(): HasMany
    {
        return $this->hasMany(ChecksBook::class, 'bank_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class, 'bank_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Bank $bank): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($bank->slug)) {
                $bank->slug = $builder->uniqueSlug($bank->name, fn (string $slug): bool => static::where('slug', $slug)->exists());
            }

            if (blank($bank->code)) {
                $bank->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $bank->slug);
            }
        });
    }

    protected static function newFactory(): BankFactory
    {
        return BankFactory::new();
    }
}
