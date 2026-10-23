<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Support\DashboardKpiQuery;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getTableQuery(): Builder|Relation|null
    {
        return DashboardKpiQuery::applyTickets(parent::getTableQuery());
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
