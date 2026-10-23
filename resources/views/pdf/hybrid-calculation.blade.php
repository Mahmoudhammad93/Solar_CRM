<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hybrid calculation {{ $run->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
        .muted { color: #555; }
        .disclaimer { background: #fff8e6; border: 1px solid #e6c35c; padding: 8px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .header { margin-bottom: 12px; }
        .logo { max-height: 48px; }
    </style>
</head>
<body>
@php
    $o = $run->outputs ?? [];
    $s = $run->steps ?? [];
    $a = $run->assumptions ?? [];
@endphp
<div class="header">
    @if(!empty($logoDataUri))
        <img class="logo" src="{{ $logoDataUri }}" alt="logo">
    @endif
    <h1>{{ $brand['company_name'] ?? $brand['app_name'] ?? 'Solar CRM' }}</h1>
    <div class="muted">Hybrid solar calculation report · {{ $run->reference }}</div>
    <div class="muted">Generated {{ $generatedAt->format('Y-m-d H:i') }}</div>
</div>

<div class="disclaimer">
    {{ $run->disclaimer }}
</div>

<h2>Key outputs (from stored snapshot)</h2>
<table>
    <tr><th>Metric</th><th>Value</th><th>Unit</th></tr>
    <tr><td>Recommended PV</td><td>{{ $o['pv_kwp_recommended'] ?? '' }}</td><td>kWp</td></tr>
    <tr><td>Panel count</td><td>{{ $o['panel_count'] ?? '' }}</td><td>pcs</td></tr>
    <tr><td>Daily energy</td><td>{{ $o['daily_energy_kwh'] ?? '' }}</td><td>kWh/day</td></tr>
    <tr><td>Annual generation</td><td>{{ $o['annual_generation_kwh'] ?? '' }}</td><td>kWh/year</td></tr>
    <tr><td>Battery nominal</td><td>{{ $o['battery_nominal_kwh'] ?? '' }}</td><td>kWh</td></tr>
    <tr><td>Battery Ah</td><td>{{ $o['battery_ah'] ?? '' }}</td><td>Ah @ {{ $o['system_voltage'] ?? '' }} V</td></tr>
    <tr><td>Inverter continuous</td><td>{{ $o['inverter_continuous_kw'] ?? '' }}</td><td>kW</td></tr>
    <tr><td>Est. backup time</td><td>{{ $o['estimated_backup_hours'] ?? '—' }}</td><td>h</td></tr>
    <tr><td>Self-consumed</td><td>{{ $o['self_consumed_kwh'] ?? '' }}</td><td>kWh/year</td></tr>
    <tr><td>Exported</td><td>{{ $o['exported_kwh'] ?? '' }}</td><td>kWh/year</td></tr>
    <tr><td>Annual savings</td><td>{{ $o['annual_savings'] ?? '' }}</td><td>{{ $o['currency'] ?? '' }} ({{ $o['tariff_label'] ?? 'Sample/Demo' }})</td></tr>
    <tr><td>Grand total</td><td>{{ $o['grand_total'] ?? '' }}</td><td>{{ $o['currency'] ?? '' }}</td></tr>
    <tr><td>O&amp;M (annual, separate)</td><td>{{ $o['om_annual'] ?? '' }}</td><td>{{ $o['currency'] ?? '' }}/year</td></tr>
</table>

<h2>Cost breakdown</h2>
<table>
    <tr><th>Line</th><th>Amount</th></tr>
    @foreach(($o['cost_breakdown'] ?? []) as $line => $amount)
        <tr><td>{{ $line }}</td><td>{{ $amount }}</td></tr>
    @endforeach
    <tr><td>Subtotal ex-GST</td><td>{{ $o['subtotal_ex_gst'] ?? '' }}</td></tr>
    <tr><td>GST</td><td>{{ $o['gst_amount'] ?? '' }}</td></tr>
    <tr><td>Subsidy</td><td>{{ $o['subsidy'] ?? '' }}</td></tr>
</table>

<h2>Intermediate steps</h2>
<table>
    <tr><th>Step</th><th>Value</th></tr>
    <tr><td>Connected load W</td><td>{{ $s['connected_w'] ?? '' }}</td></tr>
    <tr><td>Peak W</td><td>{{ $s['peak_w'] ?? '' }}</td></tr>
    <tr><td>Surge peak W</td><td>{{ $s['surge_peak_w'] ?? '' }}</td></tr>
    <tr><td>Effective backup kWh</td><td>{{ $s['effective_backup_kwh'] ?? '' }}</td></tr>
    <tr><td>Gen factor (PSH×PR×(1−L))</td><td>{{ $s['gen_factor'] ?? '' }}</td></tr>
    <tr><td>PV kWp required</td><td>{{ $s['pv_kwp_required'] ?? '' }}</td></tr>
</table>

<h2>Assumptions snapshot (immutable)</h2>
<table>
    <tr><th>Key</th><th>Value</th><th>Unit</th><th>Sample?</th></tr>
    @foreach($a as $key => $row)
        <tr>
            <td>{{ $key }}</td>
            <td>{{ is_bool($row['value'] ?? null) ? (($row['value'] ?? false) ? 'true' : 'false') : ($row['value'] ?? '') }}</td>
            <td>{{ $row['unit'] ?? '' }}</td>
            <td>{{ !empty($row['is_sample']) ? 'Sample/Demo' : 'sourced' }}</td>
        </tr>
    @endforeach
</table>
</body>
</html>
