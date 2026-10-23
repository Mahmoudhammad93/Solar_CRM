<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px; color: {{ $brand['accent_color'] ?? '#F59E0B' }}; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        .row { margin: 3px 0; }
        .label { display: inline-block; width: 180px; color: #666; }
        .logo { max-height: 48px; margin-bottom: 8px; }
        .footer { margin-top: 24px; font-size: 10px; color: #777; }
        .notice { font-size: 10px; color: #a00; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; font-size: 11px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    @if(!empty($logoDataUri))
        <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
    @endif
    <h1>{{ $brand['company_name'] ?? 'Solar CRM' }} — Invoice / Receipt</h1>
    <div class="meta">
        Invoice: <strong>{{ $invoice->number }}</strong> · Status: {{ $invoice->status }}<br>
        Order: {{ $invoice->order?->number }} · Date: {{ optional($invoice->invoiced_on)->format('Y-m-d') }}<br>
        {{ $brand['invoice_address'] ?? $brand['address'] ?? '' }} · GSTIN: {{ $brand['gstin'] ?? '' }}
    </div>

    <h2>Bill to</h2>
    <div class="row">{{ $invoice->customer?->name }}</div>
    <div class="row">{{ $invoice->customer?->address }}</div>

    <h2>Amounts</h2>
    <div class="row"><span class="label">Subtotal / taxable</span>{{ number_format((float)$invoice->subtotal, 2) }}</div>
    <div class="row"><span class="label">Tax total</span>{{ number_format((float)$invoice->tax_total, 2) }}</div>
    @foreach(($invoice->tax_lines ?? []) as $line)
        <div class="row"><span class="label">{{ $line['label'] ?? 'Tax' }}</span>{{ number_format((float)($line['amount'] ?? 0), 2) }}</div>
    @endforeach
    <div class="row"><span class="label">Grand total</span><strong>{{ number_format((float)$invoice->grand_total, 2) }}</strong></div>
    <div class="row"><span class="label">Paid (stored)</span>{{ number_format((float)$paid, 2) }}</div>
    <div class="row"><span class="label">Outstanding</span><strong>{{ number_format((float)$outstanding, 2) }}</strong></div>

    <h2>Payments (manual)</h2>
    <table>
        <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="right">Amount</th><th>By</th></tr></thead>
        <tbody>
        @forelse($invoice->payments as $p)
            <tr>
                <td>{{ optional($p->paid_on)->format('Y-m-d') }}</td>
                <td>{{ $p->method }}</td>
                <td>{{ $p->reference }}</td>
                <td class="right">{{ number_format((float)$p->amount, 2) }}</td>
                <td>{{ $p->recorder?->name }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No payments recorded</td></tr>
        @endforelse
        </tbody>
    </table>

    <p class="notice">Payment gateway: {{ $invoice->payment_gateway_note }}</p>
    <div class="footer">{{ $brand['footer_text'] ?? '' }}</div>
</body>
</html>
