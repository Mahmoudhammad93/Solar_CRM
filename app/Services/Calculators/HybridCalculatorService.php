<?php

namespace App\Services\Calculators;

use App\Models\CalculationRun;
use App\Models\User;
use App\Services\NumberingService;
use App\Services\SettingsService;
use App\Support\CalculatorAssumptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HybridCalculatorService
{
    public const SETTING_KEYS = [
        'calc.currency',
        'calc.hybrid.peak_sun_hours',
        'calc.hybrid.performance_ratio',
        'calc.hybrid.system_loss_factor',
        'calc.hybrid.losses_separate',
        'calc.hybrid.panel_wattage',
        'calc.hybrid.area_per_panel_m2',
        'calc.hybrid.battery_chemistry',
        'calc.hybrid.usable_dod',
        'calc.hybrid.battery_efficiency',
        'calc.hybrid.inverter_efficiency',
        'calc.hybrid.design_margin',
        'calc.hybrid.system_voltage',
        'calc.hybrid.default_simultaneity',
        'calc.hybrid.default_usage_factor',
        'calc.hybrid.self_consumption_share',
        'calc.hybrid.export_enabled',
        'calc.hybrid.export_rate',
        'calc.hybrid.tariff',
        'calc.hybrid.gst_rate',
        'calc.hybrid.panel_unit_price',
        'calc.hybrid.inverter_unit_price',
        'calc.hybrid.battery_price_per_kwh',
        'calc.hybrid.bos_flat',
        'calc.hybrid.installation_default',
        'calc.hybrid.transport_default',
        'calc.hybrid.structure_default',
        'calc.hybrid.wiring_default',
        'calc.hybrid.engineering_default',
        'calc.hybrid.om_annual_default',
        'calc.hybrid.generator_credit_factor',
        'calc.hybrid.first_year_degradation',
        'calc.hybrid.tariff_source',
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly NumberingService $numbering,
    ) {}

    /**
     * Compute without persisting.
     *
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array, assumptions: array, steps: array, outputs: array, disclaimer: string}
     */
    public function compute(array $inputs): array
    {
        $assumptions = CalculatorAssumptions::snapshotKeys($this->settings, self::SETTING_KEYS);
        $a = fn (string $key, mixed $default = null) => $assumptions[$key]['value'] ?? $default;

        $loads = $inputs['loads'] ?? [];
        if (! is_array($loads) || $loads === []) {
            throw ValidationException::withMessages(['loads' => 'At least one load row is required.']);
        }

        $defaultU = (float) $a('calc.hybrid.default_usage_factor', 1);
        $defaultS = (float) $a('calc.hybrid.default_simultaneity', 0.7);

        $connectedW = 0.0;
        $dailyWh = 0.0;
        $peakW = 0.0;
        $surgePeakW = 0.0;
        $criticalBackupWh = 0.0;
        $criticalPeakW = 0.0;

        $backupHours = $this->num($inputs, 'backup_hours', 4);

        foreach ($loads as $i => $row) {
            $qty = $this->nonNeg($row, 'qty', "loads.$i.qty");
            $w = $this->nonNeg($row, 'rated_w', "loads.$i.rated_w");
            $h = $this->nonNeg($row, 'hours_day', "loads.$i.hours_day");
            $u = isset($row['usage_factor']) ? $this->fraction($row['usage_factor'], "loads.$i.usage_factor", 0, 2) : $defaultU;
            $s = isset($row['simultaneity']) ? $this->fraction($row['simultaneity'], "loads.$i.simultaneity", 0, 1) : $defaultS;
            $g = isset($row['surge_factor']) ? $this->fraction($row['surge_factor'], "loads.$i.surge_factor", 0, 10) : 1.0;
            $critical = (bool) ($row['is_critical'] ?? false);

            $connectedW += $qty * $w;
            $dailyWh += $qty * $w * $h * $u;
            $peakW += $qty * $w * $s;
            $surgePeakW += $qty * $w * $s * $g;

            if ($critical) {
                $criticalPeakW += $qty * $w * $s;
                $criticalBackupWh += $qty * $w * min($h, $backupHours) * $u;
            }
        }

        $dailyKwh = $dailyWh / 1000;
        $backupEnergyKwh = $criticalBackupWh / 1000;
        $criticalLoadKw = $criticalPeakW / 1000;

        $generatorKw = $this->nonNeg($inputs, 'generator_support_kw', 'generator_support_kw', 0);
        $genCredit = (float) $a('calc.hybrid.generator_credit_factor', 0.8);
        $effectiveBackupKwh = max(0, $backupEnergyKwh - $generatorKw * $backupHours * $genCredit);

        $dod = $this->eff($inputs, 'usable_dod', (float) $a('calc.hybrid.usable_dod', 0.9), 'usable_dod');
        $etaB = $this->eff($inputs, 'battery_efficiency', (float) $a('calc.hybrid.battery_efficiency', 0.95), 'battery_efficiency');
        $etaI = $this->eff($inputs, 'inverter_efficiency', (float) $a('calc.hybrid.inverter_efficiency', 0.95), 'inverter_efficiency');
        $margin = $this->margin($inputs, 'design_margin', (float) $a('calc.hybrid.design_margin', 1.25));
        $voltage = $this->positive($inputs, 'system_voltage', (float) $a('calc.hybrid.system_voltage', 48), 'system_voltage');

        $denom = $dod * $etaB * $etaI;
        if ($denom <= 0) {
            throw ValidationException::withMessages(['usable_dod' => 'DoD × battery efficiency × inverter efficiency must be > 0.']);
        }
        if ($backupHours > 0 && $criticalLoadKw <= 0 && $effectiveBackupKwh <= 0) {
            throw ValidationException::withMessages([
                'loads' => 'Backup target requires at least one critical load (or reduce backup hours).',
            ]);
        }

        $batteryNominalKwh = $effectiveBackupKwh / $denom * $margin;
        $batteryUsableKwh = $batteryNominalKwh * $dod * $etaB;
        $batteryAh = $batteryNominalKwh * 1000 / $voltage;

        $inverterContKw = $this->ceilStep(($peakW / 1000) * $margin, 0.1);
        $inverterSurgeKw = $this->ceilStep(($surgePeakW / 1000) * $margin, 0.1);

        $psh = $this->positive($inputs, 'peak_sun_hours', (float) $a('calc.hybrid.peak_sun_hours', 5), 'peak_sun_hours');
        $pr = $this->eff($inputs, 'performance_ratio', (float) $a('calc.hybrid.performance_ratio', 0.75), 'performance_ratio');
        $lossesSeparate = (bool) $a('calc.hybrid.losses_separate', false);
        $loss = $lossesSeparate
            ? $this->fraction($inputs['system_loss_factor'] ?? $a('calc.hybrid.system_loss_factor', 0), 'system_loss_factor', 0, 0.9)
            : 0.0;
        $genFactor = $psh * $pr * (1 - $loss);
        if ($genFactor <= 0) {
            throw ValidationException::withMessages(['peak_sun_hours' => 'PSH × PR × (1−losses) must be > 0.']);
        }

        $panelW = $this->positive($inputs, 'panel_wattage', (float) $a('calc.hybrid.panel_wattage', 550), 'panel_wattage');
        $useMonthly = (bool) ($inputs['use_monthly_units_target'] ?? false);
        $monthlyUnits = $this->nonNeg($inputs, 'monthly_units', 'monthly_units', 0);
        $offset = $this->fraction($inputs['offset_fraction'] ?? 1, 'offset_fraction', 0, 2);
        $dailyTarget = $useMonthly && $monthlyUnits > 0
            ? ($monthlyUnits / 30) * $offset
            : $dailyKwh;

        $pvKwpRequired = $dailyTarget / $genFactor;
        $panelCount = (int) ceil($pvKwpRequired * 1000 / $panelW);

        $constrain = (bool) ($inputs['constrain_to_roof'] ?? false);
        $area = $this->nonNeg($inputs, 'usable_roof_area_m2', 'usable_roof_area_m2', 0);
        $areaPerPanel = $this->positive($inputs, 'area_per_panel_m2', (float) $a('calc.hybrid.area_per_panel_m2', 2.5), 'area_per_panel_m2');
        $maxPanels = $constrain && $area > 0 ? (int) floor($area / $areaPerPanel) : PHP_INT_MAX;
        $panelCountFinal = min($panelCount, max(0, $maxPanels));
        $pvKwpFinal = $panelCountFinal * $panelW / 1000;
        $pvKwpRoof = $maxPanels === PHP_INT_MAX ? null : $maxPanels * $panelW / 1000;

        $degrade = $this->fraction($a('calc.hybrid.first_year_degradation', 0), 'first_year_degradation', 0, 0.5);
        $annualGen = $pvKwpFinal * $psh * 365 * $pr * (1 - $loss) * (1 - $degrade);
        $annualLoad = $dailyKwh * 365;
        $share = $this->fraction(
            $inputs['self_consumption_share'] ?? $a('calc.hybrid.self_consumption_share', 0.7),
            'self_consumption_share',
            0,
            1
        );
        $selfConsumed = min($annualGen, $share * $annualGen, $annualLoad);
        $exportEnabled = (bool) ($inputs['export_enabled'] ?? $a('calc.hybrid.export_enabled', true));
        $exported = $exportEnabled ? max(0, $annualGen - $selfConsumed) : 0.0;

        $estBackupH = $criticalLoadKw > 1e-6
            ? ($batteryUsableKwh * $etaI / $criticalLoadKw)
            : null;

        $tariff = $this->nonNeg($inputs, 'tariff', 'tariff', (float) $a('calc.hybrid.tariff', 8));
        $exportRate = $this->nonNeg($inputs, 'export_rate', 'export_rate', (float) $a('calc.hybrid.export_rate', 3));
        $annualSavings = $selfConsumed * $tariff + $exported * $exportRate;

        $panelPrice = $this->nonNeg($inputs, 'panel_unit_price', 'panel_unit_price', (float) $a('calc.hybrid.panel_unit_price', 120));
        $invPrice = $this->nonNeg($inputs, 'inverter_unit_price', 'inverter_unit_price', (float) $a('calc.hybrid.inverter_unit_price', 590));
        $battPrice = $this->nonNeg($inputs, 'battery_price_per_kwh', 'battery_price_per_kwh', (float) $a('calc.hybrid.battery_price_per_kwh', 250));
        $bos = $this->nonNeg($inputs, 'bos_flat', 'bos_flat', (float) $a('calc.hybrid.bos_flat', 150));
        $install = $this->nonNeg($inputs, 'installation', 'installation', (float) $a('calc.hybrid.installation_default', 90));
        $transport = $this->nonNeg($inputs, 'transport', 'transport', (float) $a('calc.hybrid.transport_default', 120));
        $structure = $this->nonNeg($inputs, 'structure_civil', 'structure_civil', (float) $a('calc.hybrid.structure_default', 75));
        $wiring = $this->nonNeg($inputs, 'wiring_protection', 'wiring_protection', (float) $a('calc.hybrid.wiring_default', 50));
        $engineering = $this->nonNeg($inputs, 'engineering', 'engineering', (float) $a('calc.hybrid.engineering_default', 40));
        $other = $this->nonNeg($inputs, 'other_charges', 'other_charges', 0);
        $gstRate = $this->fraction($inputs['gst_rate'] ?? $a('calc.hybrid.gst_rate', 0.18), 'gst_rate', 0, 1);
        $subsidy = $this->nonNeg($inputs, 'subsidy', 'subsidy', 0);
        $om = $this->nonNeg($inputs, 'om_annual', 'om_annual', (float) $a('calc.hybrid.om_annual_default', 500));

        $costLines = [
            'panels' => $panelCountFinal * $panelPrice,
            'inverter' => $invPrice,
            'battery' => $batteryNominalKwh * $battPrice,
            'bos' => $bos,
            'structure_civil' => $structure,
            'wiring_protection' => $wiring,
            'installation' => $install,
            'transport' => $transport,
            'engineering' => $engineering,
            'other' => $other,
        ];
        $subtotal = array_sum($costLines);
        $gst = $subtotal * $gstRate;
        $grandTotal = $subtotal + $gst - $subsidy;

        $tariffLabel = CalculatorAssumptions::sampleLabel($assumptions['calc.hybrid.tariff']);

        $steps = [
            'connected_w' => round($connectedW, 3),
            'daily_wh' => round($dailyWh, 3),
            'daily_kwh' => round($dailyKwh, 6),
            'peak_w' => round($peakW, 3),
            'surge_peak_w' => round($surgePeakW, 3),
            'backup_energy_kwh' => round($backupEnergyKwh, 6),
            'effective_backup_kwh' => round($effectiveBackupKwh, 6),
            'critical_load_kw' => round($criticalLoadKw, 6),
            'battery_denom' => round($denom, 6),
            'gen_factor' => round($genFactor, 6),
            'daily_kwh_target' => round($dailyTarget, 6),
            'pv_kwp_required' => round($pvKwpRequired, 6),
            'panel_count_unconstrained' => $panelCount,
            'max_panels_roof' => $maxPanels === PHP_INT_MAX ? null : $maxPanels,
            'formula_path' => [
                'battery' => 'nominal_kWh = effective_backup_kWh / (DoD × η_b × η_i) × margin',
                'pv' => 'kWp = daily_kWh_target / (PSH × PR × (1−L))',
                'panels' => 'count = ceil(kWp × 1000 / panel_W)',
                'self_consumed' => 'min(gen, share×gen, annual_load)',
            ],
        ];

        $outputs = [
            'pv_kwp_recommended' => round($pvKwpFinal, 4),
            'panel_count' => $panelCountFinal,
            'pv_kwp_roof_constrained' => $pvKwpRoof !== null ? round($pvKwpRoof, 4) : null,
            'daily_energy_kwh' => round($dailyKwh, 4),
            'annual_generation_kwh' => round($annualGen, 2),
            'battery_nominal_kwh' => round($batteryNominalKwh, 4),
            'battery_usable_kwh' => round($batteryUsableKwh, 4),
            'battery_ah' => round($batteryAh, 2),
            'inverter_continuous_kw' => $inverterContKw,
            'inverter_surge_kw' => $inverterSurgeKw,
            'estimated_backup_hours' => $estBackupH !== null ? round($estBackupH, 3) : null,
            'self_consumed_kwh' => round($selfConsumed, 2),
            'exported_kwh' => round($exported, 2),
            'annual_savings' => round($annualSavings, 2),
            'cost_breakdown' => array_map(fn ($v) => round($v, 2), $costLines),
            'subtotal_ex_gst' => round($subtotal, 2),
            'gst_amount' => round($gst, 2),
            'subsidy' => round($subsidy, 2),
            'grand_total' => round($grandTotal, 2),
            'om_annual' => round($om, 2),
            'currency' => $a('calc.currency', 'INR'),
            'tariff_label' => $tariffLabel,
            'battery_chemistry' => $inputs['battery_chemistry'] ?? $a('calc.hybrid.battery_chemistry', 'LiFePO4'),
            'system_voltage' => $voltage,
        ];

        return [
            'inputs' => $inputs,
            'assumptions' => $assumptions,
            'steps' => $steps,
            'outputs' => $outputs,
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
                'calculator_type' => 'hybrid',
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

    public function rerun(CalculationRun $previous, ?User $user = null): CalculationRun
    {
        // Overlay live Settings defaults onto the previous run so SET-04 changes take effect
        // while preserving load table and explicit commercial overrides where still present.
        $inputs = $previous->inputs ?? [];
        $refresh = [
            'peak_sun_hours' => 'calc.hybrid.peak_sun_hours',
            'performance_ratio' => 'calc.hybrid.performance_ratio',
            'panel_wattage' => 'calc.hybrid.panel_wattage',
            'usable_dod' => 'calc.hybrid.usable_dod',
            'battery_efficiency' => 'calc.hybrid.battery_efficiency',
            'inverter_efficiency' => 'calc.hybrid.inverter_efficiency',
            'design_margin' => 'calc.hybrid.design_margin',
            'system_voltage' => 'calc.hybrid.system_voltage',
            'self_consumption_share' => 'calc.hybrid.self_consumption_share',
            'export_enabled' => 'calc.hybrid.export_enabled',
            'tariff' => 'calc.hybrid.tariff',
            'export_rate' => 'calc.hybrid.export_rate',
            'gst_rate' => 'calc.hybrid.gst_rate',
            'panel_unit_price' => 'calc.hybrid.panel_unit_price',
            'inverter_unit_price' => 'calc.hybrid.inverter_unit_price',
            'battery_price_per_kwh' => 'calc.hybrid.battery_price_per_kwh',
            'installation' => 'calc.hybrid.installation_default',
            'transport' => 'calc.hybrid.transport_default',
        ];
        foreach ($refresh as $inputKey => $settingKey) {
            $inputs[$inputKey] = $this->settings->get($settingKey, $inputs[$inputKey] ?? null);
        }

        return $this->runAndStore($inputs, $user, [
            'lead_id' => $previous->lead_id,
            'customer_id' => $previous->customer_id,
            'visit_id' => $previous->visit_id,
            'rerun_of_id' => $previous->id,
        ]);
    }

    private function ceilStep(float $value, float $step): float
    {
        if ($step <= 0) {
            return $value;
        }

        return ceil($value / $step) * $step;
    }

    private function num(array $data, string $key, float $default): float
    {
        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return $default;
        }
        if (! is_numeric($data[$key])) {
            throw ValidationException::withMessages([$key => 'Must be a number.']);
        }

        return (float) $data[$key];
    }

    private function nonNeg(array $data, string $key, string $field, float $default = 0): float
    {
        $v = $this->num($data, $key, $default);
        if ($v < 0) {
            throw ValidationException::withMessages([$field => 'Must be ≥ 0.']);
        }

        return $v;
    }

    private function positive(array $data, string $key, float $default, string $field): float
    {
        $v = $this->num($data, $key, $default);
        if ($v <= 0) {
            throw ValidationException::withMessages([$field => 'Must be > 0.']);
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

    private function eff(array $data, string $key, float $default, string $field): float
    {
        $v = array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== ''
            ? $data[$key]
            : $default;

        return $this->fraction($v, $field, 0.0001, 1.0);
    }

    private function margin(array $data, string $key, float $default): float
    {
        $v = $this->num($data, $key, $default);
        if ($v < 1) {
            throw ValidationException::withMessages([$key => 'Design margin must be ≥ 1.']);
        }

        return $v;
    }
}
