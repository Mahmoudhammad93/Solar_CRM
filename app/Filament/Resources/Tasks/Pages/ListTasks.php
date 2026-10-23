<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string | int, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'due_today' => Tab::make('Due today')
                ->modifyQueryUsing(fn (Builder $query) => $query->dueToday())
                ->badge(fn () => TaskResource::getEloquentQuery()->dueToday()->count()),
            'overdue' => Tab::make('Overdue')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(fn () => TaskResource::getEloquentQuery()->overdue()->count())
                ->badgeColor('danger'),
            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function (Builder $q) {
                    $q->whereNull('status')->orWhereNotIn('status', ['completed', 'cancelled']);
                })),
        ];
    }
}
