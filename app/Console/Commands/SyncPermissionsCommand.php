<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\General\System\Application;
use Modules\General\System\Module;
use Modules\General\System\SubModule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Generates every Spatie permission from the existing module/sub_module/
 * application `code` hierarchy instead of a hand-maintained list — the
 * navigation tree's authorization hooks (RecordReferenceAccess,
 * NavigationTreeService) already call auth()->user()->can($permission_name),
 * they just had nothing to check against until this command populates it.
 *
 * Modules and sub_modules only ever get view/update (a submodule has no
 * create/delete/export/print concept of its own). Applications get the six
 * standard actions plus whatever custom_actions they declare (e.g. "post").
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Generate permissions from the module/sub_module/application code hierarchy and stamp permission_name on each.';

    /** @var array<int, string> */
    private const NAVIGATION_NODE_ACTIONS = ['view', 'update'];

    /** @var array<int, string> */
    private const APPLICATION_STANDARD_ACTIONS = ['view', 'create', 'update', 'delete', 'export', 'print'];

    public function handle(): int
    {
        $created = 0;

        $created += $this->syncNavigationNodes(Module::query()->get());
        $created += $this->syncNavigationNodes(SubModule::query()->get());
        $created += $this->syncApplications();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Permissions synced. {$created} newly created.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Module|SubModule>  $nodes
     */
    private function syncNavigationNodes($nodes): int
    {
        $created = 0;

        foreach ($nodes as $node) {
            foreach (self::NAVIGATION_NODE_ACTIONS as $action) {
                $created += $this->ensurePermission("{$node->code}.{$action}");
            }

            $viewPermission = "{$node->code}.view";

            if (blank($node->permission_name) || $node->permission_name !== $viewPermission) {
                $node->forceFill(['permission_name' => $viewPermission])->save();
            }
        }

        return $created;
    }

    private function syncApplications(): int
    {
        $created = 0;

        foreach (Application::query()->get() as $application) {
            $actions = [...self::APPLICATION_STANDARD_ACTIONS, ...($application->custom_actions ?? [])];

            foreach (array_unique($actions) as $action) {
                $created += $this->ensurePermission("{$application->code}.{$action}");
            }

            $viewPermission = "{$application->code}.view";

            if (blank($application->permission_name) || $application->permission_name !== $viewPermission) {
                $application->forceFill(['permission_name' => $viewPermission])->save();
            }
        }

        return $created;
    }

    private function ensurePermission(string $name): int
    {
        $permission = Permission::firstOrNew(['name' => $name, 'guard_name' => 'web']);

        if ($permission->exists) {
            return 0;
        }

        $permission->save();

        return 1;
    }
}
