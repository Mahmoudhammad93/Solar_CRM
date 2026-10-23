<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Visit Report {{ $visit->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px; color: {{ $brand['accent_color'] ?? '#F59E0B' }}; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        .row { margin: 3px 0; }
        .label { display: inline-block; width: 180px; color: #666; }
        .logo { max-height: 48px; margin-bottom: 8px; }
        .footer { margin-top: 24px; font-size: 10px; color: #777; }
        .notice { font-size: 10px; color: #888; margin-top: 8px; }
    </style>
</head>
<body>
    @if(!empty($logoDataUri))
        <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
    @endif
    <h1>{{ $brand['company_name'] ?? 'Solar CRM' }} — Site Visit Report</h1>
    <div class="meta">
        Reference: <strong>{{ $visit->reference }}</strong><br>
        Generated: {{ $generatedAt->format('Y-m-d H:i') }} UTC<br>
        {{ $brand['address'] ?? '' }} · {{ $brand['phone'] ?? '' }} · {{ $brand['support_email'] ?? '' }}
    </div>

    <h2>Visit</h2>
    <div class="row"><span class="label">Status</span>{{ $visit->status }}</div>
    <div class="row"><span class="label">Scheduled</span>{{ optional($visit->scheduled_at)->format('Y-m-d H:i') }}</div>
    <div class="row"><span class="label">Surveyor</span>{{ $visit->surveyor?->name }}</div>
    <div class="row"><span class="label">Lead</span>{{ $visit->lead?->reference }} — {{ $visit->lead?->name }}</div>
    <div class="row"><span class="label">Customer</span>{{ $visit->customer?->customer_number }} — {{ $visit->customer?->name }}</div>
    <div class="row"><span class="label">Site address</span>{{ $visit->site_address }}</div>
    <div class="row"><span class="label">GPS</span>
        @if($visit->gps_lat && $visit->gps_lng)
            {{ $visit->gps_lat }}, {{ $visit->gps_lng }} (Demo preview — no live map)
        @else
            Not provided
        @endif
    </div>

    <h2>Survey</h2>
    <div class="row"><span class="label">Property type</span>{{ $visit->property_type }}</div>
    <div class="row"><span class="label">Roof type/material</span>{{ $visit->roof_type_material }}</div>
    <div class="row"><span class="label">Usable area (m²)</span>{{ $visit->usable_area_sqm }}</div>
    <div class="row"><span class="label">Orientation / tilt</span>{{ $visit->orientation_tilt }}</div>
    <div class="row"><span class="label">Shading / obstructions</span>{{ $visit->shading_obstructions }}</div>
    <div class="row"><span class="label">Structural concerns</span>{{ $visit->structural_concerns }}</div>
    <div class="row"><span class="label">Site access</span>{{ $visit->site_access_notes }}</div>
    <div class="row"><span class="label">Safety notes</span>{{ $visit->safety_notes }}</div>

    <h2>Utility</h2>
    <div class="row"><span class="label">Monthly units</span>{{ $visit->monthly_units }}</div>
    <div class="row"><span class="label">Sanctioned load (kW)</span>{{ $visit->sanctioned_load_kw }}</div>
    <div class="row"><span class="label">Phase</span>{{ $visit->phase_type }}</div>
    <div class="row"><span class="label">Utility / DISCOM</span>{{ $visit->utility_discom }}</div>
    <div class="row"><span class="label">Meter / connection</span>{{ $visit->meter_connection_type }}</div>
    <div class="row"><span class="label">Proposed hybrid</span>{{ $visit->proposed_hybrid_system }}</div>
    <div class="row"><span class="label">Tentative capacity (kW)</span>{{ $visit->tentative_capacity_kw }}</div>
    <div class="row"><span class="label">Bill evidence notes</span>{{ $visit->bill_consumption_evidence }}</div>

    <h2>Outcome</h2>
    <div class="row"><span class="label">Measurements</span>{{ $visit->measurements }}</div>
    <div class="row"><span class="label">Feasibility</span>{{ $visit->feasibility }}</div>
    <div class="row"><span class="label">Customer needs</span>{{ $visit->customer_needs }}</div>
    <div class="row"><span class="label">Objections</span>{{ $visit->objections }}</div>
    <div class="row"><span class="label">Feedback</span>{{ $visit->feedback }}</div>
    <div class="row"><span class="label">Recommended next step</span>{{ $visit->recommended_next_step }}</div>
    <div class="row"><span class="label">Preliminary BOM</span>{{ $visit->preliminary_bom }}</div>

    <div class="notice">Indicative site survey summary for CRM use — not an engineering certification.</div>
    <div class="footer">{{ $brand['footer_text'] ?? '' }} · File: visit-report-{{ $visit->reference }}</div>
</body>
</html>
