<?php

namespace Tests\Feature\Calculators;

use App\Services\Calculators\EvCalculatorService;
use App\Services\Calculators\InvestmentCalculatorService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvestmentAndEvCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
    }

    public function test_investment_payback_and_sensitivity(): void
    {
        $svc = app(InvestmentCalculatorService::class);
        $base = $svc->compute([
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

        // Year1 savings = 8000*10 = 80000; payback = 2 years on 100000
        $this->assertEquals(100000.0, $base['outputs']['net_initial']);
        $this->assertEquals(80000.0, $base['outputs']['annual_savings_year1']);
        $this->assertSame(2, $base['outputs']['payback_years']);

        $higherTariff = $base['inputs'];
        $higherTariff['utility_tariff'] = 20;
        $this->assertSame(1, $svc->compute($higherTariff)['outputs']['payback_years']);

        $zeroInterest = $svc->compute([
            'upfront_cost' => 120000,
            'subsidy' => 20000,
            'loan_principal' => 60000,
            'annual_interest_rate' => 0,
            'tenure_months' => 12,
            'annual_generation_kwh' => 5000,
            'self_consumption_share' => 1,
            'utility_tariff' => 8,
            'export_price' => 0,
            'degradation_per_year' => 0,
            'tariff_escalation_per_year' => 0,
            'annual_om' => 0,
            'analysis_years' => 5,
        ]);
        $this->assertEqualsWithDelta(5000.0, $zeroInterest['steps']['emi'], 0.01);
    }

    public function test_ev_utilization_scales_sessions_only(): void
    {
        $svc = app(EvCalculatorService::class);
        $full = $svc->compute([
            'charger_power_kw' => 7,
            'points' => 2,
            'sessions_per_day' => 4,
            'kwh_per_session' => 10,
            'operating_days_month' => 30,
            'selling_price' => 20,
            'purchase_cost' => 5,
            'utilization' => 1,
            'rent_month' => 0,
            'staff_month' => 0,
            'maintenance_month' => 0,
            'other_month' => 0,
            'capital_cost' => 100000,
        ]);
        // energy = 2*4*10*30 = 2400
        $this->assertEquals(2400.0, $full['outputs']['energy_sold_month_kwh']);

        $half = $full['inputs'];
        $half['utilization'] = 0.5;
        $halfOut = $svc->compute($half);
        $this->assertEquals(1200.0, $halfOut['outputs']['energy_sold_month_kwh']);
        $this->assertEqualsWithDelta(2.0, $halfOut['steps']['effective_sessions_day'], 0.001);

        // Profit month = 1200*(20-5)=18000; payback=100000/18000
        $this->assertEqualsWithDelta(5.56, $halfOut['outputs']['payback_months'], 0.02);
    }

    public function test_ev_rejects_negative(): void
    {
        $this->expectException(ValidationException::class);
        app(EvCalculatorService::class)->compute([
            'charger_power_kw' => 7,
            'points' => -1,
            'sessions_per_day' => 1,
            'kwh_per_session' => 1,
            'operating_days_month' => 1,
            'selling_price' => 1,
            'purchase_cost' => 1,
            'utilization' => 1,
        ]);
    }
}
