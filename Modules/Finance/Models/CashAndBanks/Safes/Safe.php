<?php

namespace Modules\Finance\Models\CashAndBanks\Safes;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\SafeFactory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * A safe/cash box within an entity. Links to employee responsible and GL account.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property int $entity_id
 * @property ?int $employee_id
 * @property ?int $gl_account_id
 */
class Safe extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-cbn-saf';

    protected $table = 'safes';

    protected $fillable = [
        'name',
        'entity_id',
        'employee_id',
        'location',
        'description',
        'gl_account_id',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Safe $safe): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($safe->slug)) {
                $safe->slug = $builder->uniqueSlug($safe->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($safe->code)) {
                $safe->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $safe->slug);
            }
        });
    }

    protected static function newFactory(): SafeFactory
    {
        return SafeFactory::new();
    }
}
