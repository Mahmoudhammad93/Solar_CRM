<?php

namespace App\Filament\Widgets;

use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class LeadSourceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Lead source mix';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = DashboardFilters::fromArray($this->pageFilters ?? [], Filament::auth()->user());
        $data = app(DashboardMetrics::class)->leadSourceMix($filters);

        return [
            'datasets' => [[
                'data' => array_values($data),
                'backgroundColor' => ['#F59E0B', '#0EA5E9', '#10B981', '#6366F1', '#EF4444', '#84CC16'],
            ]],
            'labels' => array_map(fn ($k) => $k ?: '(none)', array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
