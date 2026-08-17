<?php

namespace App\Support\Import;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Field;
use App\Support\DynamicForm\Core\Fields\CascadingRelationField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Turns one spreadsheet line into one record using an existing Dynamic Form
 * definition — the same fields, the same validation rules, and the same
 * create() the on-screen form uses. An importable table therefore inherits
 * its create form's rules for free and can never drift from them.
 *
 * The template's header row is the form's field KEYS (not labels), so a
 * relabelled or translated field never invalidates a file a user already has.
 */
class FormRowImporter
{
    public function __construct(protected DynamicForm $form) {}

    /**
     * Template header row: one column per form field, in declaration order.
     *
     * @return array<int, string>
     */
    public function headers(): array
    {
        return array_map(fn (Field $field): string => $field->getKey(), $this->form->fields());
    }

    /**
     * @param  array<string, mixed>  $row  raw cell values keyed by header
     *
     * @throws ValidationException when the line is unusable — the message is
     *                             what the import page shows next to the row.
     */
    public function import(array $row): Model
    {
        // The queue has no page and no toolbar button, so this is the only
        // place the create permission gets asked on the import path. Checked
        // per row rather than once because ImportTableJob resumes a partly
        // finished import, and a grant revoked in between must stop it.
        if (! $this->form->authorizeCreate()) {
            throw ValidationException::withMessages([
                'import' => [__('You are not allowed to create records through this form.')],
            ]);
        }

        $data = [];
        $errors = [];

        foreach ($this->form->fields() as $field) {
            $value = $this->normalize($row[$field->getKey()] ?? null);

            if (! $field instanceof CascadingRelationField) {
                $data[$field->getKey()] = $value;

                continue;
            }

            $level = $field->lastLevel();

            if ($value === null || $level === null) {
                $data[$field->getColumn()] = null;

                continue;
            }

            // ponytail: first match wins — a duplicated display name (two
            // cities called "Springfield") silently picks one. Add the parent
            // levels as extra template columns if that ever bites.
            $id = ($level->getModel())::query()
                ->where($level->getDisplayField() ?? 'name', $value)
                ->value((new ($level->getModel()))->getKeyName());

            if ($id === null) {
                $errors[$field->getKey()] = [__(':field ":value" was not found.', [
                    'field' => $field->getLabel(),
                    'value' => (string) $value,
                ])];
            }

            $data[$field->getColumn()] = $id;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        Validator::make($data, $this->form->validationRules())->validate();

        return $this->form->create($data);
    }

    /** Blank cells arrive as '' or whitespace; the rules expect null. */
    protected function normalize(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
