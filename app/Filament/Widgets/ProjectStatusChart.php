<?php

namespace App\Filament\Widgets;

use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ProjectStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Project status';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = DashboardFilters::fromArray($this->pageFilters ?? [], Filament::auth()->user());
        $data = app(DashboardMetrics::class)->projectStatusMix($filters);

        return [
            'datasets' => [[
                'data' => array_values($data),
                'backgroundColor' => ['#10B981', '#F59E0B', '#6366F1', '#EF4444'],
            ]],
            'labels' => array_map(fn ($k) => $k ?: '(none)', array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
