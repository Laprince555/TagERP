<?php

namespace Modules\HR\System\EmployeeManagement;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * Vertical-slice provider for the "hr-emp-emp" Application. Owns every
 * record-reference concern for Employee: title, route, facts, and scoping.
 */
class EmployeeRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'hr-emp-emp';
    }

    public function modelClass(): string
    {
        return Employee::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'employee_number', 'code', 'status', 'person_id'];
    }

    public function cardColumns(): array
    {
        return ['status'];
    }

    public function previewColumns(): array
    {
        return ['status', 'hire_date'];
    }

    public function title(Model $record): string
    {
        /** @var Employee $record */
        return $record->person?->full_name ?: $record->employee_number;
    }

    public function url(Model $record): ?string
    {
        return route('hr.employee-management.employees.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Employee $record */
        return array_values(array_filter([
            new RecordFact('Status', ucfirst((string) $record->status), 10),
        ]));
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function authorize(Model $record): bool
    {
        /** @var Employee $record */
        return $record->status === 'active';
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
