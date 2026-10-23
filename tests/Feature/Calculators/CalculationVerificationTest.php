<?php

namespace Tests\Feature\Calculators;

use App\Models\CalculationRun;
use App\Services\CalculationReportService;
use App\Services\Calculators\EvCalculatorService;
use App\Services\Calculators\HybridCalculatorService;
use App\Services\Calculators\InvestmentCalculatorService;
use App\Services\QuotationTotals;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validation V3 — independent hand-math reconciliation for interview predictability.
 * Tolerances: money/kWh annual ±0.02; battery kWh ±0.01; panel counts exact.
 */
class CalculationVerificationTest extends TestCase
{
    use RefreshDatabase;

    private HybridCalculatorService $hybrid;

    private InvestmentCalculatorService $investment;

    private EvCalculatorService $ev;

    private QuotationTotals $totals;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->hybrid = app(HybridCalculatorService::class);
        $this->investment = app(InvestmentCalculatorService::class);
        $this->ev = app(EvCalculatorService::class);
        $this->totals = app(QuotationTotals::class);

        $s = app(SettingsService::class);
        foreach ([
            ['finance.company_state', 'Maharashtra', 'string'],
            ['finance.tax_split_rule', 'auto', 'string'],
            ['finance.cgst_rate', 0.09, 'float'],
            ['finance.sgst_rate', 0.09, 'float'],
            ['finance.igst_rate', 0.18, 'float'],
            ['finance.round_off', 'none', 'string'],
        ] as [$key, $val, $type]) {
            $s->set($key, $val, ['group' => 'finance', 'type' => $type, 'change_summary' => 'v3'], null);
        }
    }

    public function test_h1_small_home_reconciles(): void
    {
        $r = $this->hybrid->compute($this->smallHomeInputs());
        $this->assertEqualsWithDelta(5.06, $r['outputs']['daily_energy_kwh'], 0.001);
        $this->assertEqualsWithDelta(0.9234, $r['outputs']['battery_nominal_kwh'], 0.01);
        $this->assertSame(3, $r['outputs']['panel_count']);
        $this->assertEqualsWithDelta(1.65, $r['outputs']['pv_kwp_recommended'], 0.001);
        $this->assertEqualsWithDelta(2258.44, $r['outputs']['annual_generation_kwh'], 0.02);
        $this->assertEqualsWithDelta(14679.84, $r['outputs']['annual_savings'], 0.05);
    }

    public function test_h2_heavy_load_reconciles(): void
    {
        $r = $this->hybrid->compute($this->heavyHomeInputs());
        // Hand: daily 25.06 kWh; backup crit 2.4 kWh; batt 3.6934; panels 13 → 7.15 kWp
        $this->assertEqualsWithDelta(25.06, $r['outputs']['daily_energy_kwh'], 0.001);
        $this->assertEqualsWithDelta(2.4, $r['steps']['backup_energy_kwh'], 0.001);
        $this->assertEqualsWithDelta(3.6934, $r['outputs']['battery_nominal_kwh'], 0.01);
        $this->assertSame(13, $r['outputs']['panel_count']);
        $this->assertEqualsWithDelta(7.15, $r['outputs']['pv_kwp_recommended'], 0.001);
        $this->assertEqualsWithDelta(3.7, $r['outputs']['inverter_continuous_kw'], 0.001);
    }

    public function test_h3_high_vs_low_dod_reconciles(): void
    {
        $hi = $this->hybrid->compute(array_merge($this->smallHomeInputs(), ['usable_dod' => 0.9]));
        $lo = $this->hybrid->compute(array_merge($this->smallHomeInputs(), ['usable_dod' => 0.5]));
        // Hand: 0.6/(0.9*0.95*0.95)*1.25=0.9234; 0.6/(0.5*0.95*0.95)*1.25=1.662
        $this->assertEqualsWithDelta(0.9234, $hi['outputs']['battery_nominal_kwh'], 0.01);
        $this->assertEqualsWithDelta(1.662, $lo['outputs']['battery_nominal_kwh'], 0.01);
        $this->assertGreaterThan($hi['outputs']['battery_nominal_kwh'], $lo['outputs']['battery_nominal_kwh']);
        // Ratio ≈ 0.9/0.5 = 1.8
        $this->assertEqualsWithDelta(1.8, $lo['outputs']['battery_nominal_kwh'] / $hi['outputs']['battery_nominal_kwh'], 0.01);
    }

    public function test_h4_high_vs_low_psh_reconciles(): void
    {
        $hi = $this->hybrid->compute(array_merge($this->smallHomeInputs(), ['peak_sun_hours' => 6.5]));
        $lo = $this->hybrid->compute(array_merge($this->smallHomeInputs(), ['peak_sun_hours' => 3.5]));
        // Hand: panels 2 @ 6.5 PSH; panels 4 @ 3.5 PSH
        $this->assertSame(2, $hi['outputs']['panel_count']);
        $this->assertEqualsWithDelta(1.1, $hi['outputs']['pv_kwp_recommended'], 0.001);
        $this->assertSame(4, $lo['outputs']['panel_count']);
        $this->assertEqualsWithDelta(2.2, $lo['outputs']['pv_kwp_recommended'], 0.001);
    }

    public function test_h5_with_generator_zeroes_battery_when_credit_covers_backup(): void
    {
        // Hand: backup 0.6 kWh; gen credit = 0.5 kW × 4 h × 0.8 = 1.6 → effective 0
        $r = $this->hybrid->compute(array_merge($this->smallHomeInputs(), [
            'generator_support_kw' => 0.5,
        ]));
        $this->assertEqualsWithDelta(0.0, $r['steps']['effective_backup_kwh'], 0.001);
        $this->assertEqualsWithDelta(0.0, $r['outputs']['battery_nominal_kwh'], 0.001);
        $this->assertSame(3, $r['outputs']['panel_count']); // PV unchanged
    }

    public function test_i1_investment_without_loan_reconciles(): void
    {
        $r = $this->investment->compute([
            'upfront_cost' => 100000,
            'subsidy' => 0,
            'annual_generation_kwh' => 8000,
            'self_consumption_share' => 1,
            'utility_tariff' => 10,
            'export_price' => 0,
            'degradation_per_year' => 0,
            'tariff_escalation_per_year' => 0,
            'annual_om' => 0,
            'other_annual_cost' => 0,
            'analysis_years' => 15,
        ]);
        $this->assertEqualsWithDelta(100000.0, $r['outputs']['net_initial'], 0.01);
        $this->assertEqualsWithDelta(80000.0, $r['outputs']['annual_savings_year1'], 0.01);
        $this->assertSame(2, $r['outputs']['payback_years']);
        $this->assertEqualsWithDelta(1100.0, $r['outputs']['roi_percent'], 0.05);
    }

    public function test_i2_investment_with_loan_and_subsidy_reconciles(): void
    {
        $r = $this->investment->compute([
            'upfront_cost' => 150000,
            'subsidy' => 30000,
            'loan_principal' => 60000,
            'annual_interest_rate' => 0.12,
            'tenure_months' => 24,
            'annual_generation_kwh' => 9000,
            'self_consumption_share' => 0.7,
            'utility_tariff' => 9,
            'export_price' => 3,
            'degradation_per_year' => 0,
            'tariff_escalation_per_year' => 0,
            'annual_om' => 2000,
            'other_annual_cost' => 0,
            'analysis_years' => 10,
        ]);
        // EMI hand ≈ 2824.41; Y1 savings 64800; debt 33892.90; net 28907.10; payback year 3
        $this->assertEqualsWithDelta(120000.0, $r['outputs']['net_initial'], 0.01);
        $this->assertEqualsWithDelta(2824.41, $r['steps']['emi'], 0.02);
        $this->assertEqualsWithDelta(64800.0, $r['outputs']['annual_savings_year1'], 0.05);
        $this->assertEqualsWithDelta(28907.10, $r['outputs']['net_cf_year1'], 0.1);
        $this->assertSame(3, $r['outputs']['payback_years']);
    }

    public function test_e1_ev_without_capital_reconciles(): void
    {
        $r = $this->ev->compute($this->evBaseInputs(0));
        $this->assertEqualsWithDelta(1200.0, $r['outputs']['energy_sold_month_kwh'], 0.01);
        $this->assertEqualsWithDelta(3500.0, $r['outputs']['net_profit_month'], 0.01);
        $this->assertNull($r['outputs']['payback_months']);
    }

    public function test_e2_ev_with_capital_payback_reconciles(): void
    {
        $r = $this->ev->compute($this->evBaseInputs(100000));
        // 100000 / 3500 ≈ 28.571
        $this->assertEqualsWithDelta(28.57, $r['outputs']['payback_months'], 0.02);
        $this->assertEqualsWithDelta(28.57, $r['outputs']['break_even_months'], 0.02);
    }

    public function test_q1_intra_state_quotation_totals_reconcile(): void
    {
        $r = $this->totals->calculate([
            ['sku' => 'A', 'title' => 'Panel', 'quantity' => 2, 'unit_price' => 1000, 'discount_percent' => 10, 'gst_rate' => 0.18],
        ], 'Maharashtra', 0);
        $this->assertSame('cgst_sgst', $r['tax_mode']);
        $this->assertEqualsWithDelta(2000.0, $r['subtotal'], 0.01);
        $this->assertEqualsWithDelta(200.0, $r['discount_total'], 0.01);
        $this->assertEqualsWithDelta(1800.0, $r['taxable_value'], 0.01);
        $this->assertEqualsWithDelta(162.0, $r['cgst_amount'], 0.01);
        $this->assertEqualsWithDelta(162.0, $r['sgst_amount'], 0.01);
        $this->assertEqualsWithDelta(2124.0, $r['grand_total'], 0.01);
    }

    public function test_q2_inter_state_quotation_totals_reconcile(): void
    {
        $r = $this->totals->calculate([
            ['sku' => 'A', 'title' => 'Panel', 'quantity' => 2, 'unit_price' => 1000, 'discount_percent' => 10, 'gst_rate' => 0.18],
        ], 'Karnataka', 0);
        $this->assertSame('igst', $r['tax_mode']);
        $this->assertEqualsWithDelta(324.0, $r['igst_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, $r['cgst_amount'], 0.01);
        $this->assertEqualsWithDelta(2124.0, $r['grand_total'], 0.01);
    }

    public function test_what_if_directions_match_formulas(): void
    {
        $base = $this->hybrid->compute($this->smallHomeInputs())['outputs'];

        $moreHours = $this->smallHomeInputs();
        $moreHours['loads'][0]['hours_day'] = 10;
        $this->assertGreaterThan($base['daily_energy_kwh'], $this->hybrid->compute($moreHours)['outputs']['daily_energy_kwh']);

        $moreBackup = $this->smallHomeInputs();
        $moreBackup['backup_hours'] = 8;
        $this->assertGreaterThan($base['battery_nominal_kwh'], $this->hybrid->compute($moreBackup)['outputs']['battery_nominal_kwh']);

        $biggerPanel = $this->smallHomeInputs();
        $biggerPanel['panel_wattage'] = 600;
        $this->assertLessThanOrEqual($base['panel_count'], $this->hybrid->compute($biggerPanel)['outputs']['panel_count']);

        $morePsh = $this->smallHomeInputs();
        $morePsh['peak_sun_hours'] = 6;
        $this->assertLessThanOrEqual($base['panel_count'], $this->hybrid->compute($morePsh)['outputs']['panel_count']);

        $lowerDod = $this->smallHomeInputs();
        $lowerDod['usable_dod'] = 0.5;
        $this->assertGreaterThan($base['battery_nominal_kwh'], $this->hybrid->compute($lowerDod)['outputs']['battery_nominal_kwh']);

        $lowerEff = $this->smallHomeInputs();
        $lowerEff['battery_efficiency'] = 0.8;
        $this->assertGreaterThan($base['battery_nominal_kwh'], $this->hybrid->compute($lowerEff)['outputs']['battery_nominal_kwh']);

        $hiTariff = $this->smallHomeInputs();
        $hiTariff['tariff'] = 20;
        $this->assertGreaterThan($base['annual_savings'], $this->hybrid->compute($hiTariff)['outputs']['annual_savings']);

        app(SettingsService::class)->set('calc.hybrid.losses_separate', true, [
            'group' => 'calc', 'type' => 'boolean', 'change_summary' => 'v3',
        ]);
        $loss = $this->smallHomeInputs();
        $loss['system_loss_factor'] = 0.2;
        $this->assertGreaterThan($base['panel_count'], $this->hybrid->compute($loss)['outputs']['panel_count']);

        $hiCost = $this->smallHomeInputs();
        $hiCost['installation'] = 5000;
        $this->assertGreaterThan($base['grand_total'], $this->hybrid->compute($hiCost)['outputs']['grand_total']);
    }

    public function test_pdf_html_and_snapshot_match_and_remain_immutable(): void
    {
        config(['filesystems.disks.local.root' => storage_path('app/private')]);
        $run = $this->hybrid->runAndStore($this->smallHomeInputs());
        $snapPanels = $run->outputs['panel_count'];
        $snapTotal = $run->outputs['grand_total'];
        $snapGen = $run->outputs['annual_generation_kwh'];

        $html = view('pdf.hybrid-calculation', [
            'run' => $run->fresh(),
            'brand' => ['company_name' => 'V3 Co', 'app_name' => 'Solar CRM'],
            'logoDataUri' => null,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString((string) $snapPanels, $html);
        $this->assertStringContainsString((string) $snapTotal, $html);
        $this->assertStringContainsString((string) $snapGen, $html);

        [$binary] = app(CalculationReportService::class)->generate($run->fresh());
        $this->assertStringStartsWith('%PDF', $binary);

        app(SettingsService::class)->set('calc.hybrid.peak_sun_hours', 2.0, [
            'group' => 'calc', 'type' => 'float', 'change_summary' => 'v3 immutability',
        ]);
        $fresh = CalculationRun::query()->findOrFail($run->id);
        $this->assertSame($snapPanels, $fresh->outputs['panel_count']);
        $this->assertEquals($snapTotal, $fresh->outputs['grand_total']);
        $this->assertEquals($snapGen, $fresh->outputs['annual_generation_kwh']);
    }

    /**
     * @return array<string, mixed>
     */
    private function smallHomeInputs(): array
    {
        return [
            'backup_hours' => 4,
            'peak_sun_hours' => 5,
            'performance_ratio' => 0.75,
            'panel_wattage' => 550,
            'usable_dod' => 0.9,
            'battery_efficiency' => 0.95,
            'inverter_efficiency' => 0.95,
            'design_margin' => 1.25,
            'system_voltage' => 48,
            'self_consumption_share' => 0.7,
            'export_enabled' => true,
            'tariff' => 8,
            'export_rate' => 3,
            'gst_rate' => 0.18,
            'loads' => [
                ['name' => 'LED', 'qty' => 10, 'rated_w' => 10, 'hours_day' => 5, 'is_critical' => false],
                ['name' => 'Fan', 'qty' => 2, 'rated_w' => 60, 'hours_day' => 8, 'is_critical' => false],
                ['name' => 'Fridge', 'qty' => 1, 'rated_w' => 150, 'hours_day' => 24, 'is_critical' => true],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function heavyHomeInputs(): array
    {
        $inputs = $this->smallHomeInputs();
        $inputs['backup_hours'] = 6;
        $inputs['loads'] = array_merge($inputs['loads'], [
            ['name' => 'AC', 'qty' => 2, 'rated_w' => 1500, 'hours_day' => 6, 'is_critical' => false],
            ['name' => 'Pump', 'qty' => 1, 'rated_w' => 750, 'hours_day' => 2, 'is_critical' => true],
            ['name' => 'TV', 'qty' => 1, 'rated_w' => 100, 'hours_day' => 5, 'is_critical' => false],
        ]);

        return $inputs;
    }

    /**
     * @return array<string, mixed>
     */
    private function evBaseInputs(float $capital): array
    {
        return [
            'charger_power_kw' => 7,
            'points' => 2,
            'sessions_per_day' => 4,
            'kwh_per_session' => 10,
            'operating_days_month' => 30,
            'selling_price' => 20,
            'purchase_cost' => 5,
            'utilization' => 0.5,
            'rent_month' => 5000,
            'staff_month' => 8000,
            'maintenance_month' => 1000,
            'other_month' => 500,
            'capital_cost' => $capital,
        ];
    }
}
