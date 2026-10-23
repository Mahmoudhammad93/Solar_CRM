<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quote->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 14px 0 6px; border-bottom: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 5px; text-align: left; }
        th { background: #f3f3f3; }
        .muted { color: #555; }
        .right { text-align: right; }
        .header { width: 100%; margin-bottom: 10px; }
        .sig { border: 1px dashed #999; height: 70px; margin-top: 8px; padding: 6px; }
        .demo { font-size: 9px; color: #884; }
        .qr { width: 90px; }
    </style>
</head>
<body>
@php
    $t = $revision->totals ?? [];
    $lines = $revision->lines ?? [];
    $tpl = $revision->template_texts ?? [];
@endphp
<table class="header" style="border:0">
    <tr style="border:0">
        <td style="border:0; width:70%">
            @if(!empty($logoDataUri))
                <img src="{{ $logoDataUri }}" style="max-height:42px" alt="logo"><br>
            @endif
            <h1>{{ $brand['company_name'] ?? 'Solar CRM' }}</h1>
            <div class="muted">{{ $brand['address'] ?? '' }} · {{ $brand['phone'] ?? '' }} · GSTIN {{ $brand['gstin'] ?? '' }}</div>
        </td>
        <td style="border:0; text-align:right">
            @if($qrSvg)
                <div class="qr">{!! $qrSvg !!}</div>
                <div class="muted" style="font-size:8px">Scan to verify</div>
            @endif
        </td>
    </tr>
</table>

<p>
    <strong>Quotation {{ $quote->number }}</strong> · Revision {{ $revision->revision }} · Status {{ strtoupper($revision->status) }}<br>
    Valid until {{ optional($revision->valid_until)->format('Y-m-d') }} · Type {{ $quote->proposal_type }}
    @if($revision->system_capacity_kw) · Capacity {{ $revision->system_capacity_kw }} kW @endif
</p>

<h2>Line items</h2>
<table>
    <thead>
    <tr>
        <th>SKU</th><th>Description</th><th>Qty</th><th>Unit</th><th>Price</th><th>Disc%</th><th>Taxable</th><th>GST</th><th>Total</th><th>Stock</th>
    </tr>
    </thead>
    <tbody>
    @foreach($lines as $line)
        <tr>
            <td>{{ $line['sku'] ?? '' }}</td>
            <td>{{ $line['title'] ?? $line['description'] ?? '' }}</td>
            <td class="right">{{ $line['quantity'] ?? '' }}</td>
            <td>{{ $line['unit'] ?? '' }}</td>
            <td class="right">{{ number_format((float)($line['unit_price'] ?? 0), 2) }}</td>
            <td class="right">{{ $line['discount_percent'] ?? 0 }}</td>
            <td class="right">{{ number_format((float)($line['taxable_value'] ?? 0), 2) }}</td>
            <td class="right">{{ number_format(((float)($line['gst_rate'] ?? 0))*100, 1) }}%</td>
            <td class="right">{{ number_format((float)($line['line_total'] ?? 0), 2) }}</td>
            <td>{{ $line['stock_indicator'] ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>Totals</h2>
<table>
    <tr><td>Subtotal</td><td class="right">{{ number_format((float)($t['subtotal'] ?? 0), 2) }}</td></tr>
    <tr><td>Discount</td><td class="right">{{ number_format((float)($t['discount_total'] ?? 0), 2) }}</td></tr>
    <tr><td>Taxable</td><td class="right">{{ number_format((float)($t['taxable_value'] ?? 0), 2) }}</td></tr>
    @if(($t['tax_mode'] ?? '') === 'cgst_sgst')
        <tr><td>CGST {{ number_format(((float)($t['cgst_rate'] ?? 0))*100, 1) }}%</td><td class="right">{{ number_format((float)($t['cgst_amount'] ?? 0), 2) }}</td></tr>
        <tr><td>SGST {{ number_format(((float)($t['sgst_rate'] ?? 0))*100, 1) }}%</td><td class="right">{{ number_format((float)($t['sgst_amount'] ?? 0), 2) }}</td></tr>
    @else
        <tr><td>IGST {{ number_format(((float)($t['igst_rate'] ?? 0))*100, 1) }}%</td><td class="right">{{ number_format((float)($t['igst_amount'] ?? 0), 2) }}</td></tr>
    @endif
    <tr><td>Round-off</td><td class="right">{{ number_format((float)($t['round_off'] ?? 0), 2) }}</td></tr>
    <tr><td><strong>Grand total</strong></td><td class="right"><strong>{{ $t['currency'] ?? '' }} {{ number_format((float)($t['grand_total'] ?? 0), 2) }}</strong></td></tr>
    <tr><td>Advance</td><td class="right">{{ number_format((float)($t['advance'] ?? 0), 2) }}</td></tr>
    <tr><td>Balance due</td><td class="right">{{ number_format((float)($t['balance_due'] ?? 0), 2) }}</td></tr>
</table>
<p class="muted">{{ $t['tax_label'] ?? '' }} · {{ $t['rounding_policy'] ?? '' }}</p>

@if($quote->proposal_type === 'hybrid' && !empty($revision->hybrid_assumptions))
    <h2>Hybrid assumptions</h2>
    <pre style="white-space:pre-wrap;font-size:9px">{{ json_encode($revision->hybrid_assumptions, JSON_PRETTY_PRINT) }}</pre>
@endif

@if($quote->proposal_type === 'pump' && !empty($revision->pump_details))
    <h2>Solar water-pump details</h2>
    <pre style="white-space:pre-wrap;font-size:9px">{{ json_encode($revision->pump_details, JSON_PRETTY_PRINT) }}</pre>
@endif

<h2>Scope / exclusions / warranty</h2>
<p><strong>Scope:</strong> {{ $tpl['scope'] ?? '' }}</p>
<p><strong>Exclusions:</strong> {{ $tpl['exclusions'] ?? '' }}</p>
<p><strong>Commissioning:</strong> {{ $tpl['commissioning'] ?? '' }}</p>
<p><strong>Warranty:</strong> {{ $tpl['warranty'] ?? '' }}</p>
<p><strong>Maintenance:</strong> {{ $tpl['maintenance'] ?? '' }}</p>
<p><strong>Validity:</strong> {{ $tpl['validity'] ?? '' }}</p>
<p>{{ $revision->customer_terms }}</p>

<h2>Signature</h2>
<div class="sig">
    @if($revision->signature_path)
        @php
            $sigFull = storage_path('app/public/'.$revision->signature_path);
        @endphp
        @if(file_exists($sigFull))
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($sigFull)) }}" style="max-height:60px">
        @endif
    @else
        <span class="muted">Signature area</span>
    @endif
    <div class="demo">Demo e-signature, not legally verified</div>
</div>

<p class="muted">Generated {{ $generatedAt->format('Y-m-d H:i') }} · Verify: {{ $verifyUrl }}</p>
</body>
</html>
