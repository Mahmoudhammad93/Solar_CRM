<?php

namespace App\Filament\Widgets;

use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MonthlyRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Monthly recorded revenue';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = DashboardFilters::fromArray($this->pageFilters ?? [], Filament::auth()->user());
        $rows = app(DashboardMetrics::class)->monthlyRevenue($filters);

        return [
            'datasets' => [[
                'label' => 'Payments',
                'data' => array_column($rows, 'total'),
                'borderColor' => '#0EA5E9',
                'fill' => false,
            ]],
            'labels' => array_column($rows, 'month'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
