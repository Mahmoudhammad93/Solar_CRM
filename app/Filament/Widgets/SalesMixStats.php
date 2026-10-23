<?php

namespace App\Filament\Widgets;

use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesMixStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Quote conversion & sales by member';

    protected function getStats(): array
    {
        $filters = DashboardFilters::fromArray($this->pageFilters ?? [], Filament::auth()->user());
        $metrics = app(DashboardMetrics::class);
        $conv = $metrics->quoteConversion($filters);
        $byMember = $metrics->salesByTeamMember($filters);
        $top = collect($byMember)->sortDesc()->take(3);

        $stats = [
            Stat::make('Quote conversion', $conv['rate'].'%')
                ->description($conv['accepted'].' accepted / '.$conv['sent_or_accepted'].' sent+accepted'),
        ];

        foreach ($top as $name => $total) {
            $stats[] = Stat::make((string) $name, number_format((float) $total, 2))
                ->description('Recorded payments');
        }

        if ($top->isEmpty()) {
            $stats[] = Stat::make('Sales by member', '—')->description('No payment records');
        }

        return $stats;
    }
}
