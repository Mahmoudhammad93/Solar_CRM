<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'My Orders (internal)';
    }

    public function getSubheading(): ?string
    {
        return 'Internal-only view — no customer portal in this build. Staff see scoped orders by role.';
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'pending'))
                ->badge(fn () => Order::query()->where('status', 'pending')->count()),
            'active' => Tab::make('Active')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'active'))
                ->badge(fn () => Order::query()->where('status', 'active')->count()),
            'completed' => Tab::make('Completed')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'completed')),
            'cancelled' => Tab::make('Cancelled')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'cancelled')),
            'all' => Tab::make('All'),
        ];
    }
}
