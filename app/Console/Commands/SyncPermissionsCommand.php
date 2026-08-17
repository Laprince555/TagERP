<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\General\System\Application;
use Modules\General\System\Module;
use Modules\General\System\SubModule;
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
 *
 * The generated set is authoritative: a permission this command would no
 * longer generate is deleted, so removing an Application or dropping one of
 * its custom_actions cannot leave a grantable orphan behind. Pass --prune=0
 * to keep them (e.g. while a module is temporarily unseeded).
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync {--prune=1 : Delete permissions the hierarchy no longer generates}';

    protected $description = 'Generate permissions from the module/sub_module/application code hierarchy and stamp permission_name on each.';

    /** @var array<int, string> */
    private const NAVIGATION_NODE_ACTIONS = ['view', 'update'];

    /** @var array<int, string> */
    private const APPLICATION_STANDARD_ACTIONS = ['view', 'create', 'update', 'delete', 'export', 'print'];

    /**
     * Verb templates for the description, keyed by action. Anything absent
     * falls back to the raw action name — a new custom_action still gets a
     * usable label without having to be registered here first.
     *
     * @var array<string, array{ar: string, en: string}>
     */
    private const ACTION_LABELS = [
        'view' => ['ar' => 'عرض', 'en' => 'View'],
        'create' => ['ar' => 'إنشاء', 'en' => 'Create'],
        'update' => ['ar' => 'تعديل', 'en' => 'Update'],
        'delete' => ['ar' => 'حذف', 'en' => 'Delete'],
        'export' => ['ar' => 'تصدير', 'en' => 'Export'],
        'print' => ['ar' => 'طباعة', 'en' => 'Print'],
        'post' => ['ar' => 'ترحيل', 'en' => 'Post'],
        'reverse' => ['ar' => 'عكس', 'en' => 'Reverse'],
        'approve' => ['ar' => 'اعتماد', 'en' => 'Approve'],
        'open' => ['ar' => 'فتح', 'en' => 'Open'],
        'close' => ['ar' => 'إغلاق', 'en' => 'Close'],
        'close-period' => ['ar' => 'إغلاق فترة في', 'en' => 'Close Period in'],
        'rebuild' => ['ar' => 'إعادة بناء أرصدة', 'en' => 'Rebuild Balances for'],
        'fetch' => ['ar' => 'تحديث', 'en' => 'Fetch'],
        'suspend' => ['ar' => 'إيقاف', 'en' => 'Suspend'],
        'terminate' => ['ar' => 'إنهاء خدمة', 'en' => 'Terminate'],
        'impersonate' => ['ar' => 'الدخول كـ', 'en' => 'Impersonate'],
        'reset-password' => ['ar' => 'إعادة تعيين كلمة مرور', 'en' => 'Reset Password For'],
        'block' => ['ar' => 'حظر', 'en' => 'Block'],
        'switch' => ['ar' => 'التبديل بين', 'en' => 'Switch'],
        'assign' => ['ar' => 'تعيين', 'en' => 'Assign'],
        'unassign' => ['ar' => 'سحب', 'en' => 'Unassign'],
    ];

    /** Every permission name this run generated, used to find the orphans. */
    private array $generated = [];

    private int $created = 0;

    private int $updated = 0;

    public function handle(): int
    {
        // Artisan resolves a command once and reuses that instance for every
        // later call, so run state has to be cleared here rather than at
        // declaration — otherwise a second run in the same process still
        // holds the first run's generated list and prunes nothing.
        $this->generated = [];
        $this->created = 0;
        $this->updated = 0;

        $this->syncNavigationNodes(Module::query()->get());
        $this->syncNavigationNodes(SubModule::query()->get());
        $this->syncApplications();

        $pruned = $this->option('prune') ? $this->prune() : 0;

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Permissions synced. {$this->created} created, {$this->updated} updated, {$pruned} pruned.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Module|SubModule>  $nodes
     */
    private function syncNavigationNodes(Collection $nodes): void
    {
        foreach ($nodes as $node) {
            foreach (self::NAVIGATION_NODE_ACTIONS as $action) {
                $this->ensurePermission("{$node->code}.{$action}", $action, $node->getTranslations('name'));
            }

            $this->stampPermissionName($node);
        }
    }

    private function syncApplications(): void
    {
        foreach (Application::query()->get() as $application) {
            $actions = [...self::APPLICATION_STANDARD_ACTIONS, ...($application->custom_actions ?? [])];

            foreach (array_unique($actions) as $action) {
                $this->ensurePermission("{$application->code}.{$action}", $action, $application->getTranslations('name'));
            }

            $this->stampPermissionName($application);
        }
    }

    private function stampPermissionName(Application|Module|SubModule $node): void
    {
        $viewPermission = "{$node->code}.view";

        if ($node->permission_name !== $viewPermission) {
            $node->forceFill(['permission_name' => $viewPermission])->save();
        }
    }

    /**
     * @param  array<string, string>  $nodeName  The owning node's name, keyed by locale.
     */
    private function ensurePermission(string $name, string $action, array $nodeName): void
    {
        $this->generated[] = $name;

        $permission = Permission::firstOrNew(['name' => $name, 'guard_name' => 'web']);
        $permission->setTranslations('description', $this->describe($action, $nodeName));

        if ($permission->exists && ! $permission->isDirty()) {
            return;
        }

        $permission->save();

        $permission->wasRecentlyCreated ? $this->created++ : $this->updated++;
    }

    /**
     * Deletes permissions the hierarchy no longer generates — an Application
     * that was removed, or a custom_action that was dropped. Spatie cascades
     * the role/model pivots on delete, so no grant is left pointing at a
     * permission that no longer exists.
     */
    private function prune(): int
    {
        $orphans = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $this->generated)
            ->get();

        foreach ($orphans as $orphan) {
            $orphan->delete();
        }

        return $orphans->count();
    }

    /**
     * @param  array<string, string>  $nodeName
     * @return array<string, string>
     */
    private function describe(string $action, array $nodeName): array
    {
        $verbs = self::ACTION_LABELS[$action] ?? ['ar' => $action, 'en' => $action];

        $descriptions = [];

        foreach ($verbs as $locale => $verb) {
            $descriptions[$locale] = trim($verb.' '.($nodeName[$locale] ?? ''));
        }

        return $descriptions;
    }
}
