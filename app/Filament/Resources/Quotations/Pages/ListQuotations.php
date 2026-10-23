<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Support\DashboardKpiQuery;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getTableQuery(): Builder|Relation|null
    {
        return DashboardKpiQuery::applyQuotations(parent::getTableQuery());
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
