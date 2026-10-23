<x-filament-panels::page>
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">
        {{ $this->disclaimer }}
        Tariff/export values are <strong>Sample/Demo</strong> unless Settings include state/utility, source, and effective date.
    </div>

    {{ $this->content }}

    @if ($lastOutputs)
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-2 text-lg font-semibold">Outputs · {{ $lastReference }}</h3>
                <ul class="space-y-1 text-sm">
                    <li>PV: {{ $lastOutputs['pv_kwp_recommended'] ?? '' }} kWp ({{ $lastOutputs['panel_count'] ?? '' }} panels)</li>
                    <li>Daily energy: {{ $lastOutputs['daily_energy_kwh'] ?? '' }} kWh</li>
                    <li>Annual generation: {{ $lastOutputs['annual_generation_kwh'] ?? '' }} kWh</li>
                    <li>Battery: {{ $lastOutputs['battery_nominal_kwh'] ?? '' }} kWh / {{ $lastOutputs['battery_ah'] ?? '' }} Ah</li>
                    <li>Inverter: {{ $lastOutputs['inverter_continuous_kw'] ?? '' }} kW cont. / {{ $lastOutputs['inverter_surge_kw'] ?? '' }} kW surge</li>
                    <li>Backup est.: {{ $lastOutputs['estimated_backup_hours'] ?? '—' }} h</li>
                    <li>Self-consumed / exported: {{ $lastOutputs['self_consumed_kwh'] ?? '' }} / {{ $lastOutputs['exported_kwh'] ?? '' }} kWh</li>
                    <li>Annual savings: {{ $lastOutputs['annual_savings'] ?? '' }} {{ $lastOutputs['currency'] ?? '' }} ({{ $lastOutputs['tariff_label'] ?? '' }})</li>
                    <li>Grand total: {{ $lastOutputs['grand_total'] ?? '' }} {{ $lastOutputs['currency'] ?? '' }}</li>
                    <li>O&amp;M (separate): {{ $lastOutputs['om_annual'] ?? '' }}/year</li>
                </ul>
            </div>
            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-2 text-lg font-semibold">Intermediates / formula path</h3>
                <ul class="space-y-1 text-sm">
                    <li>Connected W: {{ $lastSteps['connected_w'] ?? '' }}</li>
                    <li>Peak / surge W: {{ $lastSteps['peak_w'] ?? '' }} / {{ $lastSteps['surge_peak_w'] ?? '' }}</li>
                    <li>Effective backup kWh: {{ $lastSteps['effective_backup_kwh'] ?? '' }}</li>
                    <li>Gen factor: {{ $lastSteps['gen_factor'] ?? '' }}</li>
                    <li>PV kWp required: {{ $lastSteps['pv_kwp_required'] ?? '' }}</li>
                    @foreach (($lastSteps['formula_path'] ?? []) as $k => $v)
                        <li class="text-gray-600 dark:text-gray-300"><code>{{ $k }}</code>: {{ $v }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</x-filament-panels::page>
