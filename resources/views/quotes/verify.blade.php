<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quote verification · {{ $number }}</title>
    <style>
        body { font-family: Georgia, serif; background: #f6f3ee; color: #1a1a1a; margin: 0; padding: 2rem; }
        .card { max-width: 28rem; margin: 2rem auto; background: #fff; padding: 1.5rem 1.75rem; border: 1px solid #ddd; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        .muted { color: #666; font-size: 0.9rem; }
        dl { margin: 1rem 0 0; }
        dt { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #777; margin-top: 0.75rem; }
        dd { margin: 0.15rem 0 0; font-size: 1.05rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>{{ $company_name }}</h1>
    <p class="muted">Public quotation verification (minimal fields only)</p>
    <dl>
        <dt>Quote number</dt>
        <dd>{{ $number }}@if($revision) · rev {{ $revision }}@endif</dd>
        <dt>Status</dt>
        <dd>{{ ucfirst((string) $status) }}</dd>
        <dt>Grand total</dt>
        <dd>{{ $currency }} {{ number_format((float) $grand_total, 2) }}</dd>
        <dt>Valid until</dt>
        <dd>{{ $valid_until ?? '—' }}</dd>
    </dl>
</div>
</body>
</html>
