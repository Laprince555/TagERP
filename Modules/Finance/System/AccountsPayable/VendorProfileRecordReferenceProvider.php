<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\AccountsPayable\VendorProfile;

/**
 * Vertical-slice provider for the "fin-ap-vpr" Application.
 */
class VendorProfileRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return VendorProfile::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return VendorProfile::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'vendor_id', 'financial_role', 'currency_id', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['financial_role'];
    }

    public function previewColumns(): array
    {
        return ['financial_role'];
    }

    public function title(Model $record): string
    {
        /** @var VendorProfile $record */
        return $record->vendor->company->name.' — '.$record->financial_role->label();
    }

    public function url(Model $record): ?string
    {
        return route('finance.accounts-payable.vendor-profiles.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var VendorProfile $record */
        return [
            new RecordFact('Role', $record->financial_role->label(), 10),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true)->with('vendor.company');
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
