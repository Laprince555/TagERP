<?php

namespace App\Console\Commands;

use App\Support\Organization\EmployeePermissionSynchronizer;
use Illuminate\Console\Command;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * Rebuilds every linked employee's Spatie permissions/roles from
 * the grant tables and reports any drift found. EmployeePermissionSynchronizer
 * is the only writer, so drift here can only mean a sync call was missed
 * somewhere (a direct DB edit, a queued job that failed) — this is the
 * backstop, run on a schedule, that catches it instead of letting it rot.
 */
class ReconcileEmployeePermissionsCommand extends Command
{
    protected $signature = 'hr:permissions:reconcile {--dry-run : Report drift without writing any changes}';

    protected $description = 'Rebuild every active employee\'s permissions/roles from the grant tables and report drift.';

    public function handle(EmployeePermissionSynchronizer $synchronizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $drifted = 0;
        $checked = 0;

        // Deliberately not filtered to active/undeleted employees: resolve()
        // already returns an empty set for those, and they are precisely the
        // rows a missed sync leaves holding permissions they should have lost.
        Employee::withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->with(['user.permissions', 'user.roles', 'jobGrade'])
            ->chunkById(200, function ($employees) use ($synchronizer, $dryRun, &$drifted, &$checked): void {
                foreach ($employees as $employee) {
                    $checked++;

                    $current = [
                        'permissions' => $employee->user->getPermissionNames()->sort()->values()->all(),
                        'roles' => $employee->user->getRoleNames()->sort()->values()->all(),
                    ];

                    $target = $synchronizer->resolve($employee);
                    $target['permissions'] = collect($target['permissions'])->sort()->values()->all();
                    $target['roles'] = collect($target['roles'])->sort()->values()->all();

                    if ($current === $target) {
                        continue;
                    }

                    $drifted++;
                    $this->warn("Drift on employee #{$employee->id} ({$employee->code}):");
                    $this->line('  permissions current: '.implode(', ', $current['permissions']));
                    $this->line('  permissions target:  '.implode(', ', $target['permissions']));
                    $this->line('  roles current: '.implode(', ', $current['roles']));
                    $this->line('  roles target:  '.implode(', ', $target['roles']));

                    if (! $dryRun) {
                        $synchronizer->sync($employee);
                    }
                }
            });

        if ($dryRun) {
            $this->info("Dry run: {$checked} employees checked, {$drifted} drifted. Re-run without --dry-run to fix.");
        } else {
            $this->info("{$checked} employees checked, {$drifted} had drifted and were corrected.");
        }

        return self::SUCCESS;
    }
}
