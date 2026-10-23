<?php

namespace App\Filament\Widgets;

use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class LeadPipelineChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Lead-stage pipeline';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = DashboardFilters::fromArray($this->pageFilters ?? [], Filament::auth()->user());
        $data = app(DashboardMetrics::class)->leadStagePipeline($filters);
        $labels = array_map(fn ($k) => $k ?: '(none)', array_keys($data));

        return [
            'datasets' => [[
                'label' => 'Leads',
                'data' => array_values($data),
                'backgroundColor' => '#F59E0B',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
