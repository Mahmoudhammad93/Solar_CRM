<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Support\DashboardKpiQuery;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getTableQuery(): Builder|Relation|null
    {
        return DashboardKpiQuery::applyProjects(parent::getTableQuery());
    }
}
