<x-filament-panels::page>
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm">{{ $this->disclaimer }}</div>
    {{ $this->content }}
    @if ($lastOutputs)
        <div class="mt-6 rounded-xl border p-4 text-sm">
            <h3 class="mb-2 text-lg font-semibold">Outputs · {{ $lastReference }}</h3>
            <ul class="space-y-1">
                <li>Net initial: {{ $lastOutputs['net_initial'] ?? '' }} {{ $lastOutputs['currency'] ?? '' }}</li>
                <li>Year-1 savings: {{ $lastOutputs['annual_savings_year1'] ?? '' }}</li>
                <li>Year-1 net CF: {{ $lastOutputs['net_cf_year1'] ?? '' }}</li>
                <li>Payback years: {{ $lastOutputs['payback_years'] ?? 'n/a' }}</li>
                <li>ROI %: {{ $lastOutputs['roi_percent'] ?? 'n/a' }}</li>
                <li>NPV/IRR: Not implemented (bonus)</li>
            </ul>
        </div>
    @endif
</x-filament-panels::page>
