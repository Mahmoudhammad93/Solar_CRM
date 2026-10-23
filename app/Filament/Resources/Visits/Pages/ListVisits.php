<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Support\DashboardKpiQuery;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListVisits extends ListRecords
{
    protected static string $resource = VisitResource::class;

    protected function getTableQuery(): Builder|Relation|null
    {
        return DashboardKpiQuery::applyVisits(parent::getTableQuery());
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
