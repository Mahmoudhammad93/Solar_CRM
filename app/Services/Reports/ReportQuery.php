<?php

namespace App\Services\Reports;

use App\Support\DashboardFilters;
use Illuminate\Support\Collection;

/**
 * One query definition feeds both on-screen tables and CSV/PDF exports.
 *
 * @phpstan-type ReportColumn array{key: string, label: string, align?: string}
 */
abstract class ReportQuery
{
    public function __construct(
        protected DashboardFilters $filters,
    ) {}

    abstract public function key(): string;

    abstract public function title(): string;

    /**
     * @return list<ReportColumn>
     */
    abstract public function columns(): array;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    abstract public function rows(): Collection;

    /**
     * @return array<string, int|float|string>
     */
    public function totals(): array
    {
        return ['rows' => $this->rows()->count()];
    }

    public static function make(string $key, DashboardFilters $filters): self
    {
        return match ($key) {
            'leads' => new LeadReportQuery($filters),
            'customers' => new CustomerReportQuery($filters),
            'quotations' => new QuotationReportQuery($filters),
            'sales' => new SalesRevenueReportQuery($filters),
            'orders_projects' => new OrderProjectReportQuery($filters),
            'product_tax' => new ProductTaxReportQuery($filters),
            'tickets_amc' => new TicketAmcReportQuery($filters),
            'monthly' => new MonthlyBusinessReportQuery($filters),
            default => throw new \InvalidArgumentException("Unknown report: {$key}"),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function catalog(): array
    {
        return [
            'leads' => 'Lead report',
            'customers' => 'Customer report',
            'quotations' => 'Quotation report',
            'sales' => 'Sales / revenue report',
            'orders_projects' => 'Order / project report',
            'product_tax' => 'Product / tax report',
            'tickets_amc' => 'Ticket / AMC report',
            'monthly' => 'Monthly business report',
        ];
    }
}
