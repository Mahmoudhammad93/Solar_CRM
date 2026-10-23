<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use App\Services\ReportExportService;
use App\Services\Reports\ReportQuery;
use App\Support\DashboardFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ReportsHub extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected string $view = 'filament.pages.reports-hub';

    public ?array $data = [];

    public string $reportKey = 'leads';

    public string $sortColumn = '';

    public string $sortDirection = 'asc';

    public int $perPage = 25;

    public int $page = 1;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasPermission('reports.view') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'report' => 'leads',
            'startDate' => now()->subDays(30)->toDateString(),
            'endDate' => now()->toDateString(),
        ]);
        $this->reportKey = 'leads';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Report filters')->schema([
                    Select::make('report')->options(ReportQuery::catalog())->required()->live()
                        ->afterStateUpdated(function ($state): void {
                            $this->reportKey = (string) $state;
                            $this->page = 1;
                            $this->sortColumn = '';
                        }),
                    DatePicker::make('startDate')->label('From')->live(onBlur: true)
                        ->afterStateUpdated(fn () => $this->page = 1),
                    DatePicker::make('endDate')->label('To')->live(onBlur: true)
                        ->afterStateUpdated(fn () => $this->page = 1),
                    Select::make('branch_id')->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))->searchable()->live()
                        ->afterStateUpdated(fn () => $this->page = 1),
                    Select::make('team_member_id')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->live()
                        ->afterStateUpdated(fn () => $this->page = 1),
                    Select::make('lead_source')->label('Lead source')
                        ->options(fn () => Lead::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source', 'source'))
                        ->searchable()->live()
                        ->afterStateUpdated(fn () => $this->page = 1),
                    Select::make('status')->label('Status')
                        ->options([
                            'new' => 'New',
                            'qualified' => 'Qualified',
                            'won' => 'Won',
                            'lost' => 'Lost',
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'accepted' => 'Accepted',
                            'active' => 'Active',
                            'open' => 'Open',
                            'closed' => 'Closed',
                        ])->live()
                        ->afterStateUpdated(fn () => $this->page = 1),
                    Select::make('perPage')->label('Page size')->options([
                        25 => '25',
                        50 => '50',
                        100 => '100',
                    ])->default(25)->live()
                        ->afterStateUpdated(function ($state): void {
                            $this->perPage = (int) ($state ?: 25);
                            $this->page = 1;
                        }),
                ])->columns(4),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->visible(fn () => Filament::auth()->user()?->hasPermission('reports.export') ?? false)
                ->action(function (): StreamedResponse {
                    $this->assertExportPermission();
                    $report = $this->report();
                    $csv = app(ReportExportService::class)->toCsv($report);

                    return response()->streamDownload(
                        fn () => print ($csv),
                        'report-'.$report->key().'-'.now()->format('Ymd-His').'.csv',
                        ['Content-Type' => 'text/csv; charset=UTF-8'],
                    );
                }),
            Action::make('exportPdf')
                ->label('Export PDF')
                ->visible(fn () => Filament::auth()->user()?->hasPermission('reports.export') ?? false)
                ->action(function (): StreamedResponse {
                    $this->assertExportPermission();
                    $report = $this->report();
                    [$binary, $filename] = app(ReportExportService::class)->toPdf($report);

                    return response()->streamDownload(fn () => print ($binary), $filename, ['Content-Type' => 'application/pdf']);
                }),
        ];
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->page = 1;
    }

    public function nextPage(): void
    {
        if ($this->page < $this->lastPage) {
            $this->page++;
        }
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function getReportInstanceProperty(): ReportQuery
    {
        return $this->report();
    }

    public function getColumnsProperty(): array
    {
        return $this->report()->columns();
    }

    public function getTotalsProperty(): array
    {
        return $this->report()->totals();
    }

    public function getSortedRowsProperty(): Collection
    {
        $rows = $this->report()->rows();
        if ($this->sortColumn !== '') {
            $rows = $this->sortDirection === 'desc'
                ? $rows->sortByDesc($this->sortColumn)->values()
                : $rows->sortBy($this->sortColumn)->values();
        }

        return $rows;
    }

    public function getPagedRowsProperty(): Collection
    {
        $perPage = max(1, (int) ($this->data['perPage'] ?? $this->perPage));
        $this->perPage = $perPage;

        return $this->sortedRows->forPage($this->page, $perPage)->values();
    }

    public function getLastPageProperty(): int
    {
        $total = $this->sortedRows->count();
        $perPage = max(1, $this->perPage);

        return max(1, (int) ceil($total / $perPage));
    }

    public function getFilteredRowCountProperty(): int
    {
        return $this->sortedRows->count();
    }

    private function report(): ReportQuery
    {
        $state = $this->data ?: [];
        $this->reportKey = (string) ($state['report'] ?? $this->reportKey);
        $filters = DashboardFilters::fromArray($state, Filament::auth()->user());

        return ReportQuery::make($this->reportKey, $filters);
    }

    private function assertExportPermission(): void
    {
        if (! Filament::auth()->user()?->hasPermission('reports.export')) {
            Notification::make()->title('Export denied')->danger()->send();
            abort(403, 'reports.export required');
        }
    }
}
