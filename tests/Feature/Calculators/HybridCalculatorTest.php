<?php

namespace Tests\Feature\Calculators;

use App\Models\CalculationRun;
use App\Services\CalculationReportService;
use App\Services\Calculators\HybridCalculatorService;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HybridCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private HybridCalculatorService $hybrid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->hybrid = app(HybridCalculatorService::class);
    }

    /**
     * Example A — small residential (hand arithmetic in comments).
     *
     * Loads: 10×10W×5h=500Wh; 2×60W×8h=960Wh; 1×150W×24h crit=3600Wh → daily=5060Wh=5.06kWh
     * connected=370W; peak@0.7=259W
     * backup 4h critical energy=150×4=600Wh=0.6kWh
     * battery_nom=0.6/(0.9×0.95×0.95)×1.25 = 0.92336 kWh
     * Ah=0.92336×1000/48=19.2367
     * PV: 5.06/(5×0.75)=1.34933 kWp → ceil(1349.33/550)=3 panels → 1.65 kWp
     * annual_gen = 1.65×5×365×0.75 = 2258.4375 → 2258.44
     */
    public function test_example_a_small_residential_matches_hand_math(): void
    {
        $result = $this->hybrid->compute($this->baseInputs());
        $o = $result['outputs'];
        $s = $result['steps'];

        $this->assertEqualsWithDelta(370.0, $s['connected_w'], 0.01);
        $this->assertEqualsWithDelta(5.06, $o['daily_energy_kwh'], 0.001);
        $this->assertEqualsWithDelta(0.9234, $o['battery_nominal_kwh'], 0.01);
        $this->assertEqualsWithDelta(19.24, $o['battery_ah'], 0.05);
        $this->assertSame(3, $o['panel_count']);
        $this->assertEqualsWithDelta(1.65, $o['pv_kwp_recommended'], 0.001);
        $this->assertEqualsWithDelta(2258.44, $o['annual_generation_kwh'], 0.02);
    }

    /**
     * Example B — longer backup + extra critical load increases battery.
     * Add router 20W×24h critical; backup 8h.
     * critical Wh = 150×8 + 20×8 = 1200+160=1360 → 1.36 kWh
     * battery_nom=1.36/0.81225×1.25 ≈ 2.0927 kWh
     */
    public function test_example_b_higher_backup_increases_battery(): void
    {
        $inputs = $this->baseInputs();
        $inputs['backup_hours'] = 8;
        $inputs['loads'][] = ['name' => 'Router', 'qty' => 1, 'rated_w' => 20, 'hours_day' => 24, 'is_critical' => true];

        $a = $this->hybrid->compute($this->baseInputs());
        $b = $this->hybrid->compute($inputs);

        $this->assertGreaterThan($a['outputs']['battery_nominal_kwh'], $b['outputs']['battery_nominal_kwh']);
        $this->assertEqualsWithDelta(2.093, $b['outputs']['battery_nominal_kwh'], 0.02);
    }

    /**
     * Example C — roof constraint: area 5 m² / 2.5 = 2 panels max < unconstrained 3.
     */
    public function test_example_c_roof_constraint_caps_panels(): void
    {
        $inputs = $this->baseInputs();
        $inputs['constrain_to_roof'] = true;
        $inputs['usable_roof_area_m2'] = 5;
        $inputs['area_per_panel_m2'] = 2.5;

        $o = $this->hybrid->compute($inputs)['outputs'];
        $this->assertSame(2, $o['panel_count']);
        $this->assertEqualsWithDelta(1.1, $o['pv_kwp_recommended'], 0.001);
    }

    public function test_sensitivity_directions(): void
    {
        $base = $this->hybrid->compute($this->baseInputs())['outputs'];

        $moreQty = $this->baseInputs();
        $moreQty['loads'][0]['qty'] = 80; // enough to push panel count above 3
        $moreQtyOut = $this->hybrid->compute($moreQty)['outputs'];
        $this->assertGreaterThan($base['daily_energy_kwh'], $moreQtyOut['daily_energy_kwh']);
        $this->assertGreaterThan($base['panel_count'], $moreQtyOut['panel_count']);

        $moreHours = $this->baseInputs();
        $moreHours['loads'][2]['hours_day'] = 24; // already 24
        $moreHours['loads'][0]['hours_day'] = 10;
        $this->assertGreaterThan($base['daily_energy_kwh'], $this->hybrid->compute($moreHours)['outputs']['daily_energy_kwh']);

        $moreBackup = $this->baseInputs();
        $moreBackup['backup_hours'] = 8;
        $this->assertGreaterThan($base['battery_nominal_kwh'], $this->hybrid->compute($moreBackup)['outputs']['battery_nominal_kwh']);

        $biggerPanel = $this->baseInputs();
        $biggerPanel['panel_wattage'] = 600;
        $this->assertLessThanOrEqual($base['panel_count'], $this->hybrid->compute($biggerPanel)['outputs']['panel_count']);

        $morePsh = $this->baseInputs();
        $morePsh['peak_sun_hours'] = 6;
        $this->assertLessThanOrEqual($base['panel_count'], $this->hybrid->compute($morePsh)['outputs']['panel_count']);

        $lowerDod = $this->baseInputs();
        $lowerDod['usable_dod'] = 0.5;
        $this->assertGreaterThan($base['battery_nominal_kwh'], $this->hybrid->compute($lowerDod)['outputs']['battery_nominal_kwh']);

        $lowerEff = $this->baseInputs();
        $lowerEff['battery_efficiency'] = 0.8;
        $this->assertGreaterThan($base['battery_nominal_kwh'], $this->hybrid->compute($lowerEff)['outputs']['battery_nominal_kwh']);

        $higherTariff = $this->baseInputs();
        $higherTariff['tariff'] = 20;
        $this->assertGreaterThan($base['annual_savings'], $this->hybrid->compute($higherTariff)['outputs']['annual_savings']);

        $withLoss = $this->baseInputs();
        app(SettingsService::class)->set('calc.hybrid.losses_separate', true, [
            'group' => 'calc', 'type' => 'boolean', 'change_summary' => 'test',
        ]);
        $withLoss['system_loss_factor'] = 0.2;
        $lossOut = $this->hybrid->compute($withLoss)['outputs'];
        $this->assertGreaterThan($base['panel_count'], $lossOut['panel_count']);
        app(SettingsService::class)->set('calc.hybrid.losses_separate', false, [
            'group' => 'calc', 'type' => 'boolean', 'change_summary' => 'reset',
        ]);

        $exportLow = $this->baseInputs();
        $exportLow['self_consumption_share'] = 0.1;
        $exportLow['export_rate'] = 1;
        $exportHigh = $exportLow;
        $exportHigh['export_rate'] = 10;
        // PHPUnit: assertGreaterThan($expected, $actual) ⇒ actual > expected
        $this->assertGreaterThan(
            $this->hybrid->compute($exportLow)['outputs']['annual_savings'],
            $this->hybrid->compute($exportHigh)['outputs']['annual_savings']
        );

        $higherGst = $this->baseInputs();
        $higherGst['gst_rate'] = 0.28;
        $this->assertGreaterThan($base['grand_total'], $this->hybrid->compute($higherGst)['outputs']['grand_total']);

        $higherInstall = $this->baseInputs();
        $higherInstall['installation'] = 5000;
        $this->assertGreaterThan($base['grand_total'], $this->hybrid->compute($higherInstall)['outputs']['grand_total']);
    }

    public function test_snapshot_immutable_when_settings_change(): void
    {
        $run = $this->hybrid->runAndStore($this->baseInputs());
        $oldPanels = $run->outputs['panel_count'];
        $oldPsh = $run->assumptions['calc.hybrid.peak_sun_hours']['value'];

        app(SettingsService::class)->set('calc.hybrid.peak_sun_hours', 2.0, [
            'group' => 'calc', 'type' => 'float', 'unit' => 'h/day', 'change_summary' => 'test change',
        ]);

        $fresh = CalculationRun::query()->findOrFail($run->id);
        $this->assertEquals($oldPanels, $fresh->outputs['panel_count']);
        $this->assertEquals($oldPsh, $fresh->assumptions['calc.hybrid.peak_sun_hours']['value']);

        $rerun = $this->hybrid->rerun($fresh);
        $this->assertNotEquals($run->id, $rerun->id);
        $this->assertSame($run->id, $rerun->rerun_of_id);
        $this->assertGreaterThan($oldPanels, $rerun->outputs['panel_count']);
    }

    public function test_validation_rejects_negatives_and_bad_efficiency(): void
    {
        $this->expectException(ValidationException::class);
        $bad = $this->baseInputs();
        $bad['loads'][0]['qty'] = -1;
        $this->hybrid->compute($bad);
    }

    public function test_validation_rejects_efficiency_above_one(): void
    {
        $this->expectException(ValidationException::class);
        $bad = $this->baseInputs();
        $bad['battery_efficiency'] = 1.5;
        $this->hybrid->compute($bad);
    }

    public function test_pdf_matches_stored_snapshot_values(): void
    {
        config(['filesystems.disks.local.root' => storage_path('app/private')]);
        $run = $this->hybrid->runAndStore($this->baseInputs());

        // Freeze expected snapshot values used by the Blade view
        $expectedPanels = $run->outputs['panel_count'];
        $expectedTotal = $run->outputs['grand_total'];
        $expectedRef = $run->reference;

        [$binary, $filename, $path] = app(CalculationReportService::class)->generate($run->fresh());

        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertGreaterThan(500, strlen($binary));
        $this->assertFileExists($path);
        $this->assertStringEndsWith('.pdf', $filename);

        // Re-render the same Blade with the stored snapshot and assert equality of source values
        $html = view('pdf.hybrid-calculation', [
            'run' => $run->fresh(),
            'brand' => ['company_name' => 'Test Co', 'app_name' => 'Solar CRM'],
            'logoDataUri' => null,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString($expectedRef, $html);
        $this->assertStringContainsString((string) $expectedPanels, $html);
        $this->assertStringContainsString((string) $expectedTotal, $html);
        $this->assertStringContainsString('Indicative estimate', $html);

        // Mutating live settings must not change the HTML for the old run
        app(SettingsService::class)->set('calc.hybrid.panel_wattage', 100, [
            'group' => 'calc', 'type' => 'integer', 'change_summary' => 'pdf immutability',
        ]);
        $html2 = view('pdf.hybrid-calculation', [
            'run' => CalculationRun::query()->findOrFail($run->id),
            'brand' => ['company_name' => 'Test Co', 'app_name' => 'Solar CRM'],
            'logoDataUri' => null,
            'generatedAt' => now(),
        ])->render();
        $this->assertStringContainsString((string) $expectedPanels, $html2);
        $this->assertSame($expectedTotal, CalculationRun::query()->findOrFail($run->id)->outputs['grand_total']);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseInputs(): array
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
}
