<?php

namespace Modules\Finance\Livewire\CashAndBanks\Categories;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class BankCategoryRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(BankCategory::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'finance.cash-and-banks.bank-category';
    }
}
