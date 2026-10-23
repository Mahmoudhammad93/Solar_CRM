<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardActivityWidget;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\LeadPipelineChart;
use App\Filament\Widgets\LeadSourceChart;
use App\Filament\Widgets\MonthlyRevenueChart;
use App\Filament\Widgets\ProjectStatusChart;
use App\Filament\Widgets\SalesMixStats;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filters')
                ->schema([
                    DatePicker::make('startDate')->label('From'),
                    DatePicker::make('endDate')->label('To'),
                    Select::make('branch_id')->label('Branch')->options(fn () => Branch::query()->pluck('name', 'id'))->searchable(),
                    Select::make('team_member_id')->label('Team member')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable(),
                    Select::make('lead_source')->label('Lead source')->options(fn () => Lead::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source', 'source')),
                    Select::make('status')->label('Status / stage')->options([
                        'new' => 'New',
                        'qualified' => 'Qualified',
                        'won' => 'Won',
                        'lost' => 'Lost',
                        'active' => 'Active',
                        'open' => 'Open',
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                    ]),
                ])
                ->columns(3)
                ->compact(),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            DashboardStatsOverview::class,
            SalesMixStats::class,
            LeadPipelineChart::class,
            LeadSourceChart::class,
            MonthlyRevenueChart::class,
            ProjectStatusChart::class,
            DashboardActivityWidget::class,
        ];
    }
}
