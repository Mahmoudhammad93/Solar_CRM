<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Support\DashboardKpiQuery;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getTableQuery(): Builder|Relation|null
    {
        return DashboardKpiQuery::applyInvoices(parent::getTableQuery());
    }

    public function getSubheading(): ?string
    {
        return 'Visibility only — payment gateway Simulated/not implemented. Outstanding = invoice total − recorded payments.';
    }
}
