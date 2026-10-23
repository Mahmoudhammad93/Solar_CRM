<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->title() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; color: {{ $brand['accent_color'] ?? '#F59E0B' }}; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 3px 5px; text-align: left; }
        th { background: #f5f5f5; }
        .meta { color: #666; margin-bottom: 8px; }
        .totals { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>{{ $brand['company_name'] ?? 'Solar CRM' }} — {{ $report->title() }}</h1>
    <div class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }} · Rows {{ $rows->count() }}</div>
    <table>
        <thead>
            <tr>
                @foreach($report->columns() as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($report->columns() as $col)
                        <td>{{ $row[$col['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        @foreach($totals as $k => $v)
            <div><strong>{{ $k }}:</strong> {{ $v }}</div>
        @endforeach
    </div>
</body>
</html>
