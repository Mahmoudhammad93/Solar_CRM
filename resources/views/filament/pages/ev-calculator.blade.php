<x-filament-panels::page>
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm">{{ $this->disclaimer }}</div>
    {{ $this->content }}
    @if ($lastOutputs)
        <div class="mt-6 rounded-xl border p-4 text-sm">
            <h3 class="mb-2 text-lg font-semibold">Outputs · {{ $lastReference }}</h3>
            <p class="mb-2 text-gray-600">{{ $lastSteps['utilization_rule'] ?? '' }}</p>
            <ul class="space-y-1">
                <li>Energy/month: {{ $lastOutputs['energy_sold_month_kwh'] ?? '' }} kWh</li>
                <li>Revenue/month: {{ $lastOutputs['gross_revenue_month'] ?? '' }}</li>
                <li>Electricity cost/month: {{ $lastOutputs['electricity_cost_month'] ?? '' }}</li>
                <li>Opex/month: {{ $lastOutputs['opex_month'] ?? '' }}</li>
                <li>Net profit/month: {{ $lastOutputs['net_profit_month'] ?? '' }}</li>
                <li>Payback months: {{ $lastOutputs['payback_months'] ?? 'n/a' }}</li>
            </ul>
        </div>
    @endif
</x-filament-panels::page>
