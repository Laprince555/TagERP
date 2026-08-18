<?php

namespace Modules\Procurement\System\Vendors;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Procurement\Models\Vendors\Vendor;

/**
 * Vertical-slice provider for the "proc-ven-vnd" Application.
 */
class VendorRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return Vendor::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return Vendor::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'company_id', 'vendor_type', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['vendor_type'];
    }

    public function previewColumns(): array
    {
        return ['vendor_type'];
    }

    public function title(Model $record): string
    {
        /** @var Vendor $record */
        return $record->company->name;
    }

    public function url(Model $record): ?string
    {
        return route('procurement.vendor-management.vendors.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Vendor $record */
        return [
            new RecordFact('Type', $record->vendor_type->label(), 10),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true)->with('company');
    }

    public function authorize(Model $record): bool
    {
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
