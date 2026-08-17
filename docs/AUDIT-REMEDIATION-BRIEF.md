# TagERP Audit — Remediation Brief

**For:** autonomous coding agent (Claude Opus). You did NOT perform this audit; findings below come from a full-project audit on 2026-08-17. Work through tasks in order. Each task lists exact files/lines (line numbers approximate — re-verify by symbol name), the change required, and acceptance criteria. Do not refactor beyond task scope. Follow existing project conventions (see AGENTS.md/CLAUDE.md, `.agents/skills/` for Laravel/Livewire/Flux rules). Run `php artisan test` and `vendor/bin/pint --dirty` after each task.

**Stack:** Laravel 13, Livewire 3/4, Flux UI, nwidart/laravel-modules (Modules/{General,Finance,HR,CRM,Procurement,Projects}), Spatie permission, Pest.

---

## Phase 0 — NEW critical action-level findings (do BEFORE Phase 1)

These come from a second, action-level audit of every user-triggerable operation (2026-08-17). They supersede Phase 1 in priority.

### T0-1. Privilege escalation via user↔role link/unlink (MOST SEVERE IN PROJECT)
**Problem:** `Modules/General/System/System/UserRecordView.php` (~101–115): the `general.system.user.roles` sub-application allows linking roles to users with `->linkAuthorization(fn ($user, ...) => $user !== null)` (tautology) and `->unlink()` with NO authorization callback (RelationshipMutator allows when callback is null). Any authenticated user with view access to the Users page can attach ANY role (incl. super_admin) to any user — including themselves — or strip an admin's roles.
**Change:** both link and unlink must require an explicit permission (e.g. `general.system.users.manage_roles`) in the callbacks. Register the permission; grant to super_admin.
**Accept:** non-privileged user gets 403/422 on link and unlink attempts; admin succeeds.

### T0-2. Path traversal in `/docs/{path?}`
**Problem:** `Modules/General/Routes/web.php` (~127–170): the docs file server concatenates the raw `path` segment into candidate paths with no `..` rejection — `/docs/../.env` serves arbitrary files to any authenticated user.
**Change:** reject any path containing `..` (or resolve via `realpath` and verify containment within `public_path('docs')`); also deny dotfiles. Prefer rewriting over Storage with an extension allowlist.
**Accept:** `/docs/../.env` (and variants) returns 404; legitimate docs still serve.

### T0-3. JournalEditor `saveDraft`: missing permission + cross-journal line hijack
**Problem:** `Modules/Finance/Livewire/GeneralLedger/Journals/JournalEditor.php` `saveDraft()` (~215–266):
(a) no `fin-gl-jou.update` check — view-level access is the only gate;
(b) at ~245: `$journal->lines()->updateOrCreate(['id' => $row['id']], ...)` — `$row['id']` is client-controlled and never verified to belong to this journal, so a forged request can modify lines of a DIFFERENT journal, including Posted ones (the immutability guard on `Journal::updating` doesn't fire for child rows).
**Change:** (a) `abort_unless(auth()->user()?->can('fin-gl-jou.update'), 403)` at the top; (b) filter/validate row ids against `$journal->lines()->pluck('id')` and reject/drop foreign ids (fail closed), and refuse to update lines whose parent journal is not Draft.
**Accept:** forged saveDraft with a foreign/Posted line id changes nothing; user without update permission gets 403.

### T0-4. Import path bypasses form authorization
**Problem:** `app/Support/Import/FormRowImporter.php` `import()` (~41–86) validates field rules then calls `$this->form->create($data)` without ever calling `$form->authorize()`. Also `Table::canImport()` (`app/Livewire/DynamicTable/Table.php` ~1107) only checks a create form exists. Bulk user/role/person creation is possible even when the form's `authorize()` would deny.
**Change:** call `authorize()` in `FormRowImporter::import()` (or `ImportTableJob::handle()`), and make `canImport()`/`startImport()` consult the form's `authorize()`.
**Accept:** import against a form whose `authorize()` returns false aborts with a clear error; existing import tests still pass.

### T0-5. Top-level DynamicForm creates gated by view permission only
**Problem:** `app/Livewire/DynamicForm/Form.php` `save()` (~716–720): non-nested creates call `$definition->authorize()` which defaults to `true` (`app/Support/DynamicForm/Core/DynamicForm.php:77`) — no form overrides it, so "can view the table" = "can create records". Affects role creation (`RoleForm`), user creation (`UserForm` — a new user linked to an employee inherits that employee's permissions via `EmployeePermissionSynchronizer`, `UserForm.php` ~53), people/companies, and ALL HR creates incl. employees. Permission revoked after page render still allows the save.
**Change:** make non-nested `save()` also run `authorizeOutOfContext()` (which re-checks `applicationAccessible()`), OR change `DynamicForm::authorize()` default to the application check. Verify nested-create path keeps working. Then optionally override `authorize()` in `RoleForm`/`UserForm` with explicit `.create` permissions.
**Accept:** a user whose application permission was revoked (or app deactivated) after render cannot save; user without create permission on Roles/Users cannot create them.

### T0-6. SubModule applications re-parenting open to all
**Problem:** `Modules/General/System/SubModuleRecordView.php` (~75–100): route `/general/sub-modules/{recordId}/view` (`Modules/General/Routes/web.php` ~29) has only `auth` middleware and a bare `SubModule::query()`; link authorization is tautological with `allowReassignment()` — any authenticated user can move Application rows (which drive navigation and the permission namespace).
**Change:** gate the record view + link/unlink with an explicit admin permission (e.g. `general.system.submodules.manage`).

### T0-7. Person positions reassignment
**Problem:** `Modules/General/System/World/PersonRecordView.php` (~100–112): tautological link authorization + `allowReassignment()` — any viewer of a person's page can move employment-history rows between people.
**Change:** require `general.world.people.manage` (or similar) in link/unlink callbacks.

### T0-8. Reverse action offered on generated copies
**Problem:** `Modules/Finance/System/GeneralLedger/JournalRecordView.php` (~102) shows Reverse based on status only; reversing a generated copy creates a standalone reversal in one ledger only — the ledgers diverge (contradicts module's own invariants).
**Change:** hide/disable the Reverse action when `$record->isGenerated()` (guard also inside the `reverse()` handler).

## Phase 1 — Critical (do all four)

### T1. Permission-gate all seeded applications
**Problem:** Every seeded application row has `permission_name => null`, and `App\Support\RecordReference\RecordReferenceAccess::applicationAccessible()` only checks a permission when one is named. Result: any authenticated user can browse the Spatie permission catalog, all roles, all companies, and all people (incl. national IDs, passports, bank accounts, IBANs).
**Files:**
- `Modules/General/Database/Seeders/World/WorldApplicationsSeeder.php` (8 apps)
- `Modules/General/Database/Seeders/Security/ApplicationsSeeder.php` (lines ~37, 51)
- `Modules/General/Database/Seeders/System/SystemApplicationsSeeder.php`
- `Modules/Finance/Database/Seeders/GeneralLedger/ApplicationsSeeder.php`
- `Modules/HR/Database/Seeders/EmployeeManagement/ApplicationsSeeder.php`
**Change:**
1. For each seeded application, set a `permission_name` (e.g. `general.world.people.view`, `general.security.permissions.view`, `finance.gl.journals.view`, …). Follow whatever naming convention the permission sync command uses — see `app/Console/Commands/SyncPermissionsCommand.php` and add the new permissions there so `permissions:sync` creates them.
2. Ensure the seeders create/attach those permissions to the existing admin role(s) so current admins don't lose access.
3. Re-run seeders idempotently (they use upsert) — verify no duplicate rows.
**Accept:** a non-privileged authenticated user gets 404 on `/general/security/permissions`, `/general/world/people`, etc.; super_admin still sees everything; `php artisan permissions:sync` succeeds.

### T2. Fix dropped `cost_center_id` in journal reversal
**Problem:** `Modules/Finance/Services/GeneralLedger/JournalPoster.php` `reverse()` (~lines 90–106) copies journal lines but omits `cost_center_id`. `Modules/Finance/Services/GeneralLedger/JournalReplicator.php` (~line 86) copies it correctly — use it as the reference.
**Change:** add `cost_center_id` to the line-copy array in `reverse()`.
**Accept:** reversing a posted journal produces lines whose `cost_center_id` matches the originals. Add a Pest test if a Finance test harness exists; otherwise verify via tinker.

### T3. Add row locking to `post()` and `reverse()`
**Problem:** `JournalPoster.php` `post()` (~31–57) and `reverse()` (~68–125) both check `status` then act with no `lockForUpdate()` on the journal row. Concurrent calls duplicate secondary-ledger copies (JournalReplicator) and can create duplicate reversals.
**Change:** at the top of each DB::transaction, re-fetch the journal with `Journal::whereKey($journal->id)->lockForUpdate()->first()` and re-run the status assertion on the locked row. Keep the existing `journal_books` lock in `nextNumber()` untouched.
**Accept:** a Pest test with two concurrent `post()` calls (or simulation re-checking status after lock) results in exactly one posted journal and one replication set.

### T4. Include Unit tests in the Pest run
**Problem:** `tests/Pest.php` (~lines 6–8) scopes datasets `->in('Feature')` only; all 8 files in `tests/Unit/` never execute.
**Change:** `->in('Feature', 'Unit')`. Then run `php artisan test` — fix any Unit tests that fail (they were never run; failures are likely real bugs or stale expectations; report which).
**Accept:** `php artisan test` shows Unit tests executing and passing.

---

## Phase 2 — High (do in order; skip a task only if blocked, and say why)

### T5. Post/reverse/close-period authorization
**Problem:** `Modules/Finance/Livewire/GeneralLedger/Journals/JournalEditor.php` `post()` (~283) and `reverse()` (~301) require no permission beyond page visibility; `Ledger::closePeriod()/reopenPeriod()` (`Modules/Finance/Models/GeneralLedger/Ledger.php` ~110–133) have no authorization callers.
**Change:** create permissions `finance.gl.journals.post` and `finance.gl.journals.reverse`; gate both actions with `abort_unless($user->can(...))` (or `Gate::authorize`). Register permissions in sync command; grant to super_admin. If any UI calls `closePeriod` directly, gate it with `finance.gl.ledgers.close`.
**Accept:** user without the permission gets 403 on post/reverse; admin succeeds.

### T6. Person position-link authorization
**Problem:** `Modules/General/System/World/PersonRecordView.php` (~110): `->linkAuthorization(fn ($user, $parent, $candidate) => $user !== null)` — a tautology; any authenticated user can attach/reassign positions.
**Change:** require a permission (e.g. `general.world.people.manage`) in the callback. Follow the existing permission created in T1.
**Accept:** non-privileged user cannot link/unlink positions via the relation picker; admin can.

### T7. N+1 fixes (3 spots)
1. `Modules/HR/Livewire/EmployeeManagement/Employees/EmployeesTable.php` (~52): `query()` returns `Employee::query()` with no eager loads but 5 `RecordReferenceColumn`s. Add `->with(['person','entity','branch','department','jobTitle','jobGrade'])` (match actual relation names).
2. `Modules/HR/System/EmployeeManagement/EmployeeRecordView.php` (~34, ~39): same eager load; `title()` accesses `$record->person`.
3. `Modules/Finance/Livewire/GeneralLedger/Journals/JournalEditor.php` (~413): `accountOptions()` called as a method per row inside `saveDraft()`'s loop — `#[Computed]` only caches property access. Change loop body to use `$this->accountOptions->firstWhere(...)` (property access) or cache in a local variable. Also `isEditable()` (~118) calls `$this->journal()` twice — memoize.
**Accept:** no duplicate identical queries per row (verify with telescope or a test counting queries — target flat count regardless of row count).

### T8. Scope embedded tables
**Problem:** `Modules/General/Livewire/Security/Rules/RulePermissionsTable.php` (~22–25) and `Modules/General/Livewire/World/PersonPositionsTable.php` (~27–30) return bare global queries with no access check; rendered standalone they leak all rows.
**Change:** follow the module's `boot()` + `hydrate()` + `checkAccess()` convention used by standalone tables (copy the pattern from `PeopleTable.php` ~27–58) OR, if these tables only ever render embedded and the engine guarantees relation scoping, minimally add the access check in `boot()`. Also fix `RuleRecordView.php` referencing application key `general.security.rule.permissions` that isn't registered in `Modules/General/Providers/GeneralServiceProvider.php` — register it or correct the key.

### T9. Tree reparent cycle guard
**Problem:** `Modules/HR/Models/OrganizationStructure/Entity.php` (~109–118) and `Department.php` (~110–119) recompute materialized paths on parent change without checking that the new parent isn't a descendant of the node → path corruption + infinite recursion.
**Change:** before accepting a new parent, walk up (or check via path prefix: new parent's path starts with this node's path) and throw `ValidationException`/`RuntimeException` on cycle. Apply to both models identically.

### T10. Export capability gate
**Problem:** `app/Livewire/DynamicTable/Table.php` `export()` (~1069–1084) dispatches `ExportTableJob` for any user who can view the table (contrast `canBulkDelete()` ~1266 defaulting false).
**Change:** add `protected function canExport(): bool { return false; }` to the base Table, check it in `export()`, and opt in (return true) ONLY for tables where export is intended — run the app's existing export tests (`ExportBulkActionTest`, `TableImportTest`) to find which tables must opt in.

### T11. Locale-route duplication + `general.docs` collision
**Problem:** dynamic routes registered via `App\Support\ModuleRoute` use a `{locale}` prefix (~26–52) but hand-written routes in `Modules/General/Routes/web.php` (~38+) don't, creating a parallel URL space; `web.php` also declares `->name('general.docs')` colliding with the dynamically-seeded `sub_modules.route` name (`SubModulesSeeder.php` ~103).
**Change:** first investigate how the locale prefix is meant to work (read `App\Support\ModuleRoute` and `Modules/General/Routes/web.php` fully). Pick the least invasive consistent fix: either group the static routes under the same `{locale}` prefix, or leave URLs and only fix the route-name collision (rename the static docs route to `general.docs.static` or the seeded one). Do NOT break existing `route()` calls — grep for `route('general.` usages before renaming anything.
**Accept:** `php artisan route:list` shows no duplicate names; existing URLs still resolve.

### T12. Secure the default admin seeder
**Problem:** `database/seeders/RoleSeeder.php` (~24–30) always creates `admin@tagerp.com` / `password` with `super_admin` (unconditional `Gate::before` bypass, `app/Providers/AppServiceProvider.php` ~56).
**Change:** keep for local dev, but guard with environment: only create when `app()->environment('local', 'testing')` or when `app.env` is explicitly ` seeding`-flagged; in any other environment, skip (or create with a random password surfaced via console warning). Keep `DatabaseSeeder` calling it; the guard lives inside RoleSeeder.

---

### T2b. Additional High items from action audit
- **Fail-closed RelationshipMutator:** `app/Support/DynamicRecordView/Resolution/RelationshipMutator.php` (~66–67, 115–116): null link/unlink authorization callbacks mean ALLOW. Invert to deny-by-default; audit all `RelationshipActions` definitions and list which ones relied on the null-allow default (put list in final report).
- **Export gate + rate limit:** covered by T10, plus add a simple per-user throttle (e.g. max N queued exports) in `export()` (`Table.php` ~1081).
- **Cap `selectedIds`:** `Table.php` (~1041–1074) — cap array size (e.g. 500) and drop ids not present in the current authorized query.
- **HR atomicity:** wrap `Employee` saved-hooks (`Employee.php` ~113–142`: permission sync) and the Entity/Department reparent cascade (`Entity.php` ~124–131`, `Department.php` ~125–132`) in the same transaction as the parent write (pattern exists in `Branch::save()`).
- **EmployeeForm hardening:** `Modules/HR/System/EmployeeManagement/EmployeeForm.php` — add `in:` rules for select fields (entity/department scope, is_main), `unique` rule for `employee_number`, `max` for `gross_salary`.
- **Finance draft FK scoping:** `JournalEditor.php` rules (~315–317) — scope `exists` rules for account/cost-center/currency to the journal's chart and user-visible accounts (use `AccountAccessResolver`), instead of global `exists`.
- **JobGrade/JobTitle version bumps:** add `OrganizationVersion::bump()` hooks so cached permission scopes invalidate.

## Phase 3 — Medium (do as many as time allows, in this order)

### T13. Finance correctness pack
All in `Modules/Finance`:
- `JournalPoster.php` (~249–258): sequence generation `orderByDesc('number')` on padded 4-char string breaks after 9,999 — switch to numeric MAX cast or a counter column.
- `JournalsTable.php` (~80): index shows `total_debit`/`total_credit` that `System/GeneralLedger/JournalRecordView.php` (~103–108) deliberately hides from restricted users — only include the columns when `canSeeAllAccountsOf()` allows, else omit.
- `JournalEditor.php` (~357): `rules()` `'rows' => ['array']` — add `max:200`.
- `Models/GeneralLedger/JournalLine.php` (~89–94) and `JournalEditor.php` (~392–394): float casts on money — replace with `bccomp(..., 6)`.
- `editor.blade.php` (~249): `wire:key` uses array index; key by stable line id/uid instead (note `removeRow()` reindexes with `array_values`).
- `JournalEditor.php` (~264–265): `unset($this->journal); $this->mount($this->recordId);` — extract a `fillFromJournal()` helper instead of re-calling `mount()`.

### T14. HR correctness pack
- `Entity.php` / `Department.php` (~129–149): wrap recursive path cascade in `DB::transaction`; remove per-node `find()` in `refreshPath()` by passing loaded models.
- `Modules/HR/Models/OrganizationStructure/Branch.php` (`saving` hook ~66–72): wrap `is_main` demotion + save in a transaction.
- `Modules/HR/System/EmployeeManagement/EmployeeForm.php` (~101): `gross_salary` add `max:9999999999.99` (matches `decimal(12,2)`); add `termination_date` `after_or_equal:hire_date` when present.
- `Employee.php` (~122–124): run `assertOrganizationallyConsistent()` only when org-related fields are dirty (`->isDirty([...])`).

### T15. Core hardening pack
- `app/Livewire/DynamicRecordView/RelationPickerModal.php` (~197): `candidateQuery()` starts from `newQuery()`; ensure picker definitions' scoping applies — document/enforce that pickers must set `getQuery()`.
- `app/Support/DynamicRecordView/Mutation/RelationshipMutator.php` (~66–67): null authorization callback defaults to allow — invert to deny-by-default OR keep allow but add a loud comment + audit which definitions omit it (list them in your final report).
- `app/Jobs/ExportTableJob.php` (~51–53): whitelist the six known context keys instead of writing arbitrary public properties.
- `app/Jobs/ImportTableJob.php` (~45–49): null-guard `$import->user`.
- `app/Livewire/Components/NotificationBell.php`: no pruning — delete export CSVs from `exports/{userId}/` when their notification is deleted (or scheduled cleanup).
- `config/telescope.php` (~19): default `TELESCOPE_ENABLED=false`.

### T16. General module pack
- `PeopleTable.php` (~86): dead filter — `DateFilter::make('birth_date')` but column is `date_of_birth`; fix the key.
- `SubModulesSeeder.php` (~118), `WorldApplicationsSeeder.php` (~142), `ApplicationsSeeder`: after upsert, call `NavigationTreeService::invalidateCache()` (pattern exists in `ModulesSeeder.php` ~116).
- `Modules/General/Routes/web.php` `/docs/{path?}`: replace homemade static file server with `Storage`/response with an extension allowlist (`.html`, `.md` rendered, images) — at minimum deny dotfiles and add extension allowlist.
- `app/Support/RecordReference/RecordReferenceAccess.php` (~59–61): `catch (Throwable)` silently returns false — add `report($e)` (or log at warning) so config bugs aren't invisible.

### T17. Extract shared access-check trait (~30 duplicated sites)
`boot()` + `hydrate()` + `checkAccess()` + `whereRaw('1 = 0')` is copy-pasted across table components in Modules/{General,Finance,HR}. Extract one trait (e.g. `ChecksApplicationAccess`) in a shared location (app/Support or the General module) and swap every usage to `use ChecksApplicationAccess;` with an abstract `applicationCode(): string`. Replace `whereRaw('1 = 0')` with `->whereKey(-1)`. Do NOT change behavior — only deduplicate.

---

## Out of scope (do NOT touch)
- CRM / Procurement / Projects modules (empty scaffolds).
- Deleting the default admin seeder entirely (T12 guards it instead).
- Large architectural changes (islands, async actions, component format conversions).
- Translation/i18n of hardcoded labels (tracked separately; too broad for this pass).

## Final deliverable format
Per task: one-line status (done / skipped + reason), files touched, tests run and their result. End with `git diff --stat` summary and any follow-up items discovered.
