<?php

namespace Modules\General\System\System;

use App\Models\User;
use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * Create-form definition for the "gen-sys-usr" Application. Replaces the
 * old ad-hoc app/Livewire/Admin/CreateUserForm.php (routes/web.php
 * "users.create") — same defaults (theme: orange-onyx, locale: current),
 * same validation shape as Fortify's CreateNewUser action.
 *
 * The `employee` field is deliberately optional: a User is not required to
 * have an Employee row at all — an admin account belongs to the managing
 * company running this Application, not necessarily to a company the
 * system's own org structure owns. Only employees not already linked to a
 * user are offered, since employees.user_id is unique.
 */
class UserForm extends DynamicForm
{
    public function model(): string
    {
        return User::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            TextField::make('email')->type('email')->label('Email')->required()->rules(['email', 'max:255', 'unique:users,email']),
            RelationListField::make('employee')
                ->model(Employee::class)
                ->createForm('hr.employee-management.employee.create')
                ->field('employee_number')
                ->searchable(['employee_number'])
                ->query(fn ($query) => $query->whereNull('user_id'))
                ->label('Employee'),
            TextField::make('password')->type('password')->label('Password')->required()->rules([Password::default(), 'confirmed']),
            TextField::make('password_confirmation')->type('password')->label('Confirm Password')->required(),
        ];
    }

    public function create(array $data): Model
    {
        $employeeId = $data['employee_id'] ?? null;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'theme' => 'orange-onyx',
            'locale' => app()->getLocale(),
        ]);

        // Through the Eloquent instance, not a query-builder mass update, so
        // Employee's saved() hooks (EmployeePermissionSynchronizer, the
        // OrganizationVersion bump) actually fire — this new user needs its
        // permissions synced the moment it's linked, not left stale.
        if ($employeeId) {
            Employee::find($employeeId)?->update(['user_id' => $user->id]);
        }

        return $user;
    }
}
