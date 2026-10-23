<?php

namespace App\Services\Calculators;

use App\Models\CalculationRun;
use App\Models\User;
use App\Services\NumberingService;
use App\Services\SettingsService;
use App\Support\CalculatorAssumptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentCalculatorService
{
    public const SETTING_KEYS = [
        'calc.currency',
        'calc.hybrid.tariff',
        'calc.hybrid.export_rate',
        'calc.investment.default_analysis_years',
        'calc.hybrid.tariff_source',
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

        $upfront = $this->nonNeg($inputs, 'upfront_cost');
        $subsidy = $this->nonNeg($inputs, 'subsidy', 0);
        $netInitial = $upfront - $subsidy;

        $loan = $this->nonNeg($inputs, 'loan_principal', 0);
        $annualRate = $this->fraction($inputs['annual_interest_rate'] ?? 0, 'annual_interest_rate', 0, 1);
        $tenure = (int) ($inputs['tenure_months'] ?? 0);
        if ($loan > 0 && $tenure <= 0) {
            throw ValidationException::withMessages(['tenure_months' => 'Loan tenure (months) must be > 0 when principal > 0.']);
        }

        $emi = 0.0;
        if ($loan > 0 && $tenure > 0) {
            $r = $annualRate / 12;
            $emi = $r == 0.0
                ? $loan / $tenure
                : $loan * $r * pow(1 + $r, $tenure) / (pow(1 + $r, $tenure) - 1);
        }

        $years = (int) ($inputs['analysis_years'] ?? $assumptions['calc.investment.default_analysis_years']['value'] ?? 10);
        if ($years < 1 || $years > 40) {
            throw ValidationException::withMessages(['analysis_years' => 'Analysis years must be 1–40.']);
        }

        $gen0 = $this->nonNeg($inputs, 'annual_generation_kwh');
        $share = $this->fraction($inputs['self_consumption_share'] ?? 0.7, 'self_consumption_share', 0, 1);
        $tariff0 = $this->nonNeg($inputs, 'utility_tariff', (float) ($assumptions['calc.hybrid.tariff']['value'] ?? 8));
        $export0 = $this->nonNeg($inputs, 'export_price', (float) ($assumptions['calc.hybrid.export_rate']['value'] ?? 3));
        $degrade = $this->fraction($inputs['degradation_per_year'] ?? 0.005, 'degradation_per_year', 0, 0.2);
        $escalate = $this->fraction($inputs['tariff_escalation_per_year'] ?? 0.03, 'tariff_escalation_per_year', 0, 0.5);
        $om = $this->nonNeg($inputs, 'annual_om', 0);
        $other = $this->nonNeg($inputs, 'other_annual_cost', 0);

        $loanYears = $tenure > 0 ? (int) ceil($tenure / 12) : 0;
        $cashflows = [];
        $cumulative = 0.0;
        $payback = null;

        for ($t = 1; $t <= $years; $t++) {
            $gen = $gen0 * pow(1 - $degrade, $t - 1);
            $tariff = $tariff0 * pow(1 + $escalate, $t - 1);
            $export = $export0 * pow(1 + $escalate, $t - 1);
            $self = $gen * $share;
            $expKwh = $gen - $self;
            $savings = $self * $tariff + $expKwh * $export;
            $opex = $om + $other;
            $debt = ($t <= $loanYears) ? $emi * 12 : 0.0;
            $net = $savings - $opex - $debt;
            $cumulative += $net;
            if ($payback === null && $cumulative >= $netInitial) {
                $payback = $t;
            }
            $cashflows[] = [
                'year' => $t,
                'generation_kwh' => round($gen, 2),
                'savings' => round($savings, 2),
                'opex' => round($opex, 2),
                'debt_service' => round($debt, 2),
                'net_cf' => round($net, 2),
                'cumulative_cf' => round($cumulative, 2),
            ];
        }

        $sumCf = array_sum(array_column($cashflows, 'net_cf'));
        $roi = $netInitial != 0.0 ? (($sumCf - $netInitial) / $netInitial) * 100 : null;

        $year1 = $cashflows[0] ?? null;

        return [
            'inputs' => $inputs,
            'assumptions' => $assumptions,
            'steps' => [
                'emi' => round($emi, 4),
                'loan_years_active' => $loanYears,
                'formula_path' => [
                    'emi' => 'P×r×(1+r)^n/((1+r)^n−1); if r=0 then P/n',
                    'savings_t' => 'self×tariff_t + export_kwh×export_t',
                    'net_cf' => 'savings − opex − debt',
                ],
                'cashflows' => $cashflows,
            ],
            'outputs' => [
                'net_initial' => round($netInitial, 2),
                'monthly_emi' => round($emi, 2),
                'annual_savings_year1' => $year1['savings'] ?? 0,
                'annual_opex' => round($om + $other, 2),
                'net_cf_year1' => $year1['net_cf'] ?? 0,
                'payback_years' => $payback,
                'roi_percent' => $roi !== null ? round($roi, 2) : null,
                'analysis_years' => $years,
                'currency' => $assumptions['calc.currency']['value'] ?? 'INR',
                'tariff_label' => CalculatorAssumptions::sampleLabel($assumptions['calc.hybrid.tariff']),
                'npv' => null,
                'irr' => null,
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
                'calculator_type' => 'investment',
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
