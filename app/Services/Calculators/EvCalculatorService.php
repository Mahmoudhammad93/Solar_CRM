<?php

namespace App\Services\Calculators;

use App\Models\CalculationRun;
use App\Models\User;
use App\Services\NumberingService;
use App\Services\SettingsService;
use App\Support\CalculatorAssumptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvCalculatorService
{
    public const SETTING_KEYS = [
        'calc.currency',
        'calc.ev.default_utilization',
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly NumberingService $numbering,
    ) {}

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array, assumptions: array, steps: array, outputs: array, disclaimer: string}
     */
    public function compute(array $inputs): array
    {
        $assumptions = CalculatorAssumptions::snapshotKeys($this->settings, self::SETTING_KEYS);

        $powerKw = $this->positive($inputs, 'charger_power_kw');
        $points = $this->positive($inputs, 'points');
        $sessions = $this->nonNeg($inputs, 'sessions_per_day');
        $kwhSession = $this->nonNeg($inputs, 'kwh_per_session');
        $days = $this->positive($inputs, 'operating_days_month');
        $sell = $this->nonNeg($inputs, 'selling_price');
        $buy = $this->nonNeg($inputs, 'purchase_cost');
        $util = $this->fraction(
            $inputs['utilization'] ?? $assumptions['calc.ev.default_utilization']['value'] ?? 0.6,
            'utilization',
            0,
            1
        );
        $rent = $this->nonNeg($inputs, 'rent_month', 0);
        $staff = $this->nonNeg($inputs, 'staff_month', 0);
        $maint = $this->nonNeg($inputs, 'maintenance_month', 0);
        $other = $this->nonNeg($inputs, 'other_month', 0);
        $capital = $this->nonNeg($inputs, 'capital_cost', 0);

        // Utilization scales sessions only — never also kWh/session (spec §4.1)
        $effectiveSessions = $sessions * $util;
        $energyDay = $points * $effectiveSessions * $kwhSession;
        $energyMonth = $energyDay * $days;
        $energyYear = $energyMonth * 12;

        $revenueMonth = $energyMonth * $sell;
        $elecMonth = $energyMonth * $buy;
        $opexMonth = $rent + $staff + $maint + $other;
        $profitMonth = $revenueMonth - $elecMonth - $opexMonth;
        $profitYear = $profitMonth * 12;

        $paybackMonths = null;
        if ($capital > 0) {
            $paybackMonths = $profitMonth > 1e-9 ? $capital / $profitMonth : null;
        }

        return [
            'inputs' => $inputs,
            'assumptions' => $assumptions,
            'steps' => [
                'charger_power_kw' => $powerKw,
                'effective_sessions_day' => round($effectiveSessions, 4),
                'energy_day_kwh' => round($energyDay, 4),
                'utilization_rule' => 'effective_sessions = sessions_day × utilization; kWh/session not re-scaled',
                'formula_path' => [
                    'energy_month' => 'points × sessions×util × kWh/session × days',
                    'profit' => 'revenue − electricity − opex',
                ],
            ],
            'outputs' => [
                'energy_sold_month_kwh' => round($energyMonth, 2),
                'energy_sold_year_kwh' => round($energyYear, 2),
                'gross_revenue_month' => round($revenueMonth, 2),
                'gross_revenue_year' => round($revenueMonth * 12, 2),
                'electricity_cost_month' => round($elecMonth, 2),
                'opex_month' => round($opexMonth, 2),
                'net_profit_month' => round($profitMonth, 2),
                'net_profit_year' => round($profitYear, 2),
                'payback_months' => $paybackMonths !== null ? round($paybackMonths, 2) : null,
                'break_even_months' => $paybackMonths !== null ? round($paybackMonths, 2) : null,
                'currency' => $assumptions['calc.currency']['value'] ?? 'INR',
            ],
            'disclaimer' => CalculatorAssumptions::DISCLAIMER,
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @param  array{lead_id?:?int,customer_id?:?int,visit_id?:?int,rerun_of_id?:?int}  $links
     */
    public function runAndStore(array $inputs, ?User $user = null, array $links = []): CalculationRun
    {
        $payload = $this->compute($inputs);

        return DB::transaction(function () use ($payload, $user, $links) {
            return CalculationRun::query()->create([
                'reference' => $this->numbering->next('calculation'),
                'calculator_type' => 'ev',
                'lead_id' => $links['lead_id'] ?? null,
                'customer_id' => $links['customer_id'] ?? null,
                'visit_id' => $links['visit_id'] ?? null,
                'inputs' => $payload['inputs'],
                'assumptions' => $payload['assumptions'],
                'steps' => $payload['steps'],
                'outputs' => $payload['outputs'],
                'disclaimer' => $payload['disclaimer'],
                'created_by' => $user?->id,
                'rerun_of_id' => $links['rerun_of_id'] ?? null,
            ]);
        });
    }

    private function nonNeg(array $data, string $key, float $default = 0): float
    {
        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return $default;
        }
        if (! is_numeric($data[$key])) {
            throw ValidationException::withMessages([$key => 'Must be a number.']);
        }
        $v = (float) $data[$key];
        if ($v < 0) {
            throw ValidationException::withMessages([$key => 'Must be ≥ 0.']);
        }

        return $v;
    }

    private function positive(array $data, string $key): float
    {
        $v = $this->nonNeg($data, $key, 0);
        if ($v <= 0) {
            throw ValidationException::withMessages([$key => 'Must be > 0.']);
        }

        return $v;
    }

    private function fraction(mixed $value, string $field, float $min, float $max): float
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([$field => 'Must be a number.']);
        }
        $v = (float) $value;
        if ($v < $min || $v > $max) {
            throw ValidationException::withMessages([$field => "Must be between {$min} and {$max}."]);
        }

        return $v;
    }
}
