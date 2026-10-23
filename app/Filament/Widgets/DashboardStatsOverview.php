<?php

namespace App\Filament\Widgets;

use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'KPIs (stored data)';

    protected function getStats(): array
    {
        $filters = DashboardFilters::fromArray($this->pageFilters ?? [], Filament::auth()->user());
        $metrics = app(DashboardMetrics::class);
        $cards = $metrics->cards($filters);

        $fmt = fn ($v) => $v === null ? '—' : (is_float($v) ? number_format($v, 2) : (string) $v);

        return [
            Stat::make('New leads', $fmt($cards['new_leads']))->url($metrics->deepLink('new_leads', $filters)),
            Stat::make('Follow-ups due', $fmt($cards['follow_ups_due']))->url($metrics->deepLink('follow_ups_due', $filters))->color('warning'),
            Stat::make('Follow-ups overdue', $fmt($cards['follow_ups_overdue']))->url($metrics->deepLink('follow_ups_overdue', $filters))->color('danger'),
            Stat::make('Scheduled visits', $fmt($cards['scheduled_visits']))->url($metrics->deepLink('scheduled_visits', $filters)),
            Stat::make('Quotes draft', $fmt($cards['quotes_draft']))->url($metrics->deepLink('quotes_draft', $filters)),
            Stat::make('Quotes sent', $fmt($cards['quotes_sent']))->url($metrics->deepLink('quotes_sent', $filters)),
            Stat::make('Quotes accepted', $fmt($cards['quotes_accepted']))->url($metrics->deepLink('quotes_accepted', $filters)),
            Stat::make('Active projects', $fmt($cards['active_projects']))->url($metrics->deepLink('active_projects', $filters)),
            Stat::make('Open tickets', $fmt($cards['open_tickets']))->url($metrics->deepLink('open_tickets', $filters)),
            Stat::make('Recorded revenue', $fmt($cards['recorded_revenue']))->url($metrics->deepLink('recorded_revenue', $filters))->description($cards['recorded_revenue'] === null ? 'No payments yet' : 'From payment records'),
            Stat::make('Outstanding', $fmt($cards['outstanding']))->url($metrics->deepLink('outstanding', $filters))->description($cards['outstanding'] === null ? 'No payments yet' : 'Invoices − payments'),
        ];
    }
}
