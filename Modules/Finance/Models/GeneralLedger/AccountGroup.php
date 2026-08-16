<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\AccountGroupFactory;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;

/**
 * A named set of accounts, used either as a chart-building template or as the
 * unit of who-can-see-what.
 *
 * @property string $code
 * @property string $name
 * @property AccountGroupPurpose $purpose
 */
class AccountGroup extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-agr';

    protected $table = 'account_groups';

    protected $fillable = [
        'name',
        'description',
        'purpose',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => AccountGroupPurpose::class,
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_group_account');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AccountGroupAssignment::class, 'account_group_id');
    }

    protected static function booted(): void
    {
        static::creating(function (AccountGroup $group): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($group->slug)) {
                $group->slug = $builder->uniqueSlug($group->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($group->code)) {
                $group->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $group->slug);
            }
        });

        // Membership and activity both change what people can see, and the
        // resolved answer is cached, so any write here drops the cache rather
        // than leaving somebody looking at yesterday's permissions.
        static::saved(fn () => app(AccountAccessResolver::class)->flush());
        static::deleted(fn () => app(AccountAccessResolver::class)->flush());
    }

    protected static function newFactory(): AccountGroupFactory
    {
        return AccountGroupFactory::new();
    }
}
