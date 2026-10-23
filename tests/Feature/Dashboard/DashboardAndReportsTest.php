<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lead;
use App\Models\Quotation;
use App\Models\QuotationRevision;
use App\Models\User;
use App\Services\DashboardMetrics;
use App\Services\NumberingService;
use App\Services\ReportExportService;
use App\Services\Reports\LeadReportQuery;
use App\Services\Reports\ProductTaxReportQuery;
use App\Support\DashboardFilters;
use App\Support\DashboardKpiQuery;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $executive;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->admin = User::factory()->withRole('super-admin')->create();
        $this->executive = User::factory()->withRole('sales-executive')->create();
    }

    public function test_dashboard_kpi_counts_match_database(): void
    {
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'A',
            'mobile' => '9000000001',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'source' => 'web',
            'created_by' => $this->admin->id,
            'created_at' => now()->subDay(),
        ]);
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'B',
            'mobile' => '9000000002',
            'assigned_to' => $this->admin->id,
            'stage' => 'qualified',
            'source' => 'referral',
            'next_follow_up_at' => now()->setTime(15, 0),
            'created_by' => $this->admin->id,
            'created_at' => now()->subDays(2),
        ]);
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'C',
            'mobile' => '9000000003',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'source' => 'web',
            'next_follow_up_at' => now()->subDays(3),
            'created_by' => $this->admin->id,
            'created_at' => now()->subDays(5),
        ]);

        $filters = DashboardFilters::fromArray([
            'startDate' => now()->subDays(10)->toDateString(),
            'endDate' => now()->toDateString(),
        ], $this->admin);

        $cards = app(DashboardMetrics::class)->cards($filters);

        $this->assertSame(3, $cards['new_leads']);
        $this->assertSame(Lead::query()->count(), $cards['new_leads']);
        $this->assertSame(1, $cards['follow_ups_due']);
        $this->assertSame(1, $cards['follow_ups_overdue']);
        $this->assertNull($cards['recorded_revenue']);
        $this->assertNull($cards['outstanding']);
    }

    public function test_executive_only_sees_own_leads_on_dashboard(): void
    {
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Mine',
            'mobile' => '9000000010',
            'assigned_to' => $this->executive->id,
            'stage' => 'new',
            'created_by' => $this->executive->id,
        ]);
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Theirs',
            'mobile' => '9000000011',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'created_by' => $this->admin->id,
        ]);

        $execCards = app(DashboardMetrics::class)->cards(
            DashboardFilters::fromArray([], $this->executive)
        );
        $adminCards = app(DashboardMetrics::class)->cards(
            DashboardFilters::fromArray([], $this->admin)
        );

        $this->assertSame(1, $execCards['new_leads']);
        $this->assertSame(2, $adminCards['new_leads']);
    }

    public function test_lead_report_totals_equal_row_sums_and_csv_row_count(): void
    {
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'R1',
            'mobile' => '9000000020',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'source' => 'web',
            'expected_value' => 1000,
            'created_by' => $this->admin->id,
        ]);
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'R2',
            'mobile' => '9000000021',
            'assigned_to' => $this->admin->id,
            'stage' => 'qualified',
            'source' => 'web',
            'expected_value' => 2500.5,
            'created_by' => $this->admin->id,
        ]);
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Other source',
            'mobile' => '9000000022',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'source' => 'walk-in',
            'expected_value' => 999,
            'created_by' => $this->admin->id,
        ]);

        $filters = DashboardFilters::fromArray(['lead_source' => 'web'], $this->admin);
        $report = new LeadReportQuery($filters);
        $rows = $report->rows();
        $totals = $report->totals();

        $this->assertSame(2, $rows->count());
        $this->assertSame(2, $totals['rows']);
        $this->assertEquals(3500.5, $totals['expected_value_sum']);
        $this->assertEquals(round((float) $rows->sum('expected_value'), 2), $totals['expected_value_sum']);

        $csv = app(ReportExportService::class)->toCsv($report);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = preg_split("/\r\n|\n|\r/", trim($csv));
        // header + 2 data rows + blank + totals lines
        $dataLines = array_values(array_filter($lines, fn ($l) => $l !== '' && ! str_starts_with($l, 'rows') && ! str_starts_with($l, 'expected_value')));
        $this->assertCount(3, $dataLines); // header + 2 rows
        $this->assertSame($rows->count(), count($dataLines) - 1);
    }

    public function test_product_tax_summary_matches_quote_tax_totals(): void
    {
        $quote = Quotation::query()->create([
            'number' => app(NumberingService::class)->next('quote'),
            'public_token' => bin2hex(random_bytes(8)),
            'proposal_type' => 'hybrid',
            'owner_id' => $this->admin->id,
        ]);
        $rev = QuotationRevision::query()->create([
            'quotation_id' => $quote->id,
            'revision' => 1,
            'status' => 'draft',
            'totals' => [
                'subtotal' => 10000,
                'taxable_value' => 10000,
                'cgst_amount' => 900,
                'sgst_amount' => 900,
                'igst_amount' => 0,
                'grand_total' => 11800,
            ],
            'created_by' => $this->admin->id,
        ]);
        $quote->update(['current_revision_id' => $rev->id]);

        $report = new ProductTaxReportQuery(DashboardFilters::fromArray([], $this->admin));
        $totals = $report->totals();

        $this->assertEquals(1800.0, $totals['quote_tax_total']);
    }

    public function test_kpi_deep_link_query_filters_leads(): void
    {
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Due',
            'mobile' => '9000000030',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'next_follow_up_at' => now()->setTime(12, 0),
            'created_by' => $this->admin->id,
        ]);
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Not due',
            'mobile' => '9000000031',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'created_by' => $this->admin->id,
        ]);

        request()->merge([]);
        request()->query->set('kpi', 'follow_ups_due');

        $filtered = DashboardKpiQuery::applyLeads(Lead::query())->count();
        $this->assertSame(1, $filtered);

        $link = app(DashboardMetrics::class)->deepLink(
            'follow_ups_due',
            DashboardFilters::fromArray([], $this->admin)
        );
        $this->assertStringContainsString('kpi=follow_ups_due', $link);
        $this->assertStringContainsString('/admin/leads?', $link);
    }

    public function test_export_permission_required_for_reports_hub(): void
    {
        $this->assertTrue($this->admin->hasPermission('reports.export'));
        $this->assertFalse($this->executive->hasPermission('reports.export'));
        $this->assertFalse($this->executive->hasPermission('reports.view'));
        $this->assertTrue($this->admin->hasPermission('reports.view'));
    }
}
