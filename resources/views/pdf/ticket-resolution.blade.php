<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $ticket->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px; color: {{ $brand['accent_color'] ?? '#F59E0B' }}; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        .row { margin: 3px 0; }
        .label { display: inline-block; width: 180px; color: #666; }
        .logo { max-height: 48px; margin-bottom: 8px; }
        .footer { margin-top: 24px; font-size: 10px; color: #777; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; font-size: 11px; }
    </style>
</head>
<body>
    @if(!empty($logoDataUri))
        <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
    @endif
    <h1>{{ $brand['company_name'] ?? 'Solar CRM' }} — Ticket Resolution Report</h1>
    <div class="meta">
        Ticket: <strong>{{ $ticket->number }}</strong><br>
        Generated: {{ $generatedAt->format('Y-m-d H:i') }}
    </div>

    <h2>Summary</h2>
    <div class="row"><span class="label">Status</span>{{ $ticket->status }}</div>
    <div class="row"><span class="label">Priority</span>{{ $ticket->priority }}</div>
    <div class="row"><span class="label">Category</span>{{ $ticket->category }}</div>
    <div class="row"><span class="label">Customer</span>{{ $ticket->customer?->name }}</div>
    <div class="row"><span class="label">Project</span>{{ $ticket->project?->number }}</div>
    <div class="row"><span class="label">Order</span>{{ $ticket->order?->number }}</div>
    <div class="row"><span class="label">Assignee</span>{{ $ticket->assignee?->name }}</div>
    <div class="row"><span class="label">Opened</span>{{ optional($ticket->opened_at)->format('Y-m-d H:i') }}</div>
    <div class="row"><span class="label">Target resolution</span>{{ optional($ticket->target_resolution_at)->format('Y-m-d H:i') }}</div>
    <div class="row"><span class="label">Issue</span>{{ $ticket->issue_summary }}</div>
    <div class="row"><span class="label">Detail</span>{{ $ticket->issue_detail }}</div>
    <div class="row"><span class="label">Resolution notes</span>{{ $ticket->resolution_notes }}</div>
    <div class="row"><span class="label">Feedback</span>{{ $ticket->feedback }}</div>

    <h2>Status history</h2>
    <table>
        <thead><tr><th>When</th><th>From</th><th>To</th><th>By</th><th>Notes</th></tr></thead>
        <tbody>
        @foreach($ticket->statusHistories as $h)
            <tr>
                <td>{{ optional($h->created_at)->format('Y-m-d H:i') }}</td>
                <td>{{ $h->from_status }}</td>
                <td>{{ $h->to_status }}</td>
                <td>{{ $h->user?->name }}</td>
                <td>{{ $h->notes }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer">{{ $brand['footer_text'] ?? '' }} · {{ $brand['gstin'] ?? '' }}</div>
</body>
</html>
