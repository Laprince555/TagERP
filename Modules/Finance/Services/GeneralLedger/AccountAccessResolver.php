<?php

namespace Modules\Finance\Services\GeneralLedger;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountGroupPurpose;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Which accounts a person is allowed to see.
 *
 * Restriction is opt-in: somebody with no access group assigned — directly or
 * through their job title — sees everything. That keeps the system usable from
 * the first day and makes narrowing an explicit act, which is the behaviour
 * agreed for this application. Flipping the default later means changing
 * `visibleAccountIds()` and nothing else.
 *
 * This is deliberately not an Eloquent global scope. Account membership also
 * answers structural questions — is this account a leaf, is it in the ledger's
 * chart, does the tree hang together — and those answers must not depend on
 * who is looking. Instead every user-facing entry point calls restrict(), and
 * a test holds each of them to it.
 */
class AccountAccessResolver
{
    private const CACHE_PREFIX = 'finance.account-access.user.';

    private const VERSION_KEY = 'finance.account-access.version';

    private const CACHE_TTL = 900;

    /**
     * The accounts this user may see, or null when they are unrestricted.
     *
     * @return array<int, int>|null
     */
    public function visibleAccountIds(?User $user = null): ?array
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            // No authenticated user means system context — a queued job, a
            // seeder, the console. Those are not "somebody with no groups".
            return null;
        }

        return Cache::remember(
            $this->cacheKey($user),
            self::CACHE_TTL,
            fn (): ?array => $this->resolveFor($user),
        );
    }

    /**
     * Narrow an accounts query to what the user may see. A no-op for an
     * unrestricted user, so callers never need to branch.
     */
    public function restrict(Builder $query, ?User $user = null, string $column = 'accounts.id'): Builder
    {
        $visible = $this->visibleAccountIds($user);

        if ($visible === null) {
            return $query;
        }

        return $query->whereIn($column, $visible);
    }

    /**
     * Whether the user may see every account this journal touches.
     *
     * All or nothing on purpose: showing four of six lines would leak the
     * journal's shape while claiming to be complete, and the totals would not
     * add up against what is on screen.
     */
    public function canSeeAllAccountsOf(Journal $journal, ?User $user = null): bool
    {
        $visible = $this->visibleAccountIds($user);

        if ($visible === null) {
            return true;
        }

        $lineAccountIds = $journal->lines()->distinct()->pluck('account_id')->all();

        return array_diff($lineAccountIds, $visible) === [];
    }

    /**
     * Invalidate every user's resolved answer at once.
     *
     * Done by bumping a version that is part of each key, rather than clearing
     * the cache: a permission change must not also throw away the navigation
     * tree and the route table, which is what a blanket flush would do. The
     * stale entries simply age out on their own TTL.
     */
    public function flush(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }

    public function forgetUser(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    private function cacheKey(User $user): string
    {
        return self::CACHE_PREFIX.$user->getKey().'.v'.$this->version();
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }

    /**
     * @return array<int, int>|null
     */
    private function resolveFor(User $user): ?array
    {
        // Unscoped on purpose. Employee carries an organization global scope,
        // which answers "may the current viewer see this employee" — the wrong
        // question here, where we are establishing who the viewer *is*. Asking
        // it scoped returns nothing and hands the user unrestricted access,
        // which is the one way this resolver must never fail.
        $employee = $user->employee()->withoutGlobalScopes()->first();

        if ($employee === null) {
            return null;
        }

        $groupIds = AccountGroup::query()
            ->where('purpose', AccountGroupPurpose::Access->value)
            ->where('is_active', true)
            ->whereHas('assignments', function (Builder $assignments) use ($employee): void {
                $assignments
                    ->where(function (Builder $direct) use ($employee): void {
                        $direct->where('assignable_type', $employee->getMorphClass())
                            ->where('assignable_id', $employee->getKey());
                    })
                    ->when($employee->job_title_id, function (Builder $query) use ($employee): void {
                        $query->orWhere(function (Builder $byTitle) use ($employee): void {
                            $byTitle->where('assignable_type', (new JobTitle)->getMorphClass())
                                ->where('assignable_id', $employee->job_title_id);
                        });
                    });
            })
            ->pluck('id');

        if ($groupIds->isEmpty()) {
            return null;
        }

        return AccountGroup::query()
            ->whereIn('account_groups.id', $groupIds)
            ->join('account_group_account', 'account_group_account.account_group_id', '=', 'account_groups.id')
            ->distinct()
            ->pluck('account_group_account.account_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
