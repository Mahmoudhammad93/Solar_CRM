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
            // Parameter must be named $query — Filament injects by name; $q resolves
            // app(Builder::class) with a null model and breaks filters.
            'pending' => Tab::make('Pending')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => Order::query()->where('status', 'pending')->count()),
            'active' => Tab::make('Active')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(fn () => Order::query()->where('status', 'active')->count()),
            'completed' => Tab::make('Completed')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'cancelled' => Tab::make('Cancelled')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
            'all' => Tab::make('All'),
        ];
    }
}
