<?php

namespace Modules\General\System\Security;

use App\Models\Role;
use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\TextField;
use Illuminate\Database\Eloquent\Model;

/**
 * Create-form definition for the "gen-sec-rul" Application. A Rule is a
 * Spatie Role under a business-facing name — this form only ever creates
 * the named bundle. Attaching permissions/job-titles/employees to it
 * happens afterward against the saved record (see RuleRecordView's
 * read-only tabs) — the same "create first, attach after" shape already
 * used for Department↔Entity and JobTitle↔JobGrade.
 */
class RuleForm extends DynamicForm
{
    public function model(): string
    {
        return Role::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Rule Name')->required(),
        ];
    }

    public function create(array $data): Model
    {
        return Role::create([...$data, 'guard_name' => 'web']);
    }
}
