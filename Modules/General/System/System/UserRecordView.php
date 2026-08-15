<?php

namespace Modules\General\System\System;

use App\Models\User;
use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;

/**
 * The authorized record show page for a single User (gen-sys-usr
 * Application). Mirrors CompanyRecordView's shape.
 */
class UserRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.system.user';

    public function model(): string
    {
        return User::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sys-usr');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return User::query()->whereRaw('1 = 0');
        }

        return User::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->email;
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('basic-information')
                        ->heading('Basic Information')
                        ->fields([
                            TextViewField::make('name')->label('Name'),
                            TextViewField::make('email')->label('Email')->copyable(),
                            // Not a RecordReferenceViewField: that component requires a
                            // BelongsTo relation, and here the FK is owned by Employee
                            // (employees.user_id), so User::employee() is a HasOne.
                            RelationViewField::make('employee.employee_number')->label('Employee'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
