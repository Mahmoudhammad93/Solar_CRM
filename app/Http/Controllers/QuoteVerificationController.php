<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Services\SettingsService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuoteVerificationController extends Controller
{
    public function show(string $token, SettingsService $settings): View|Response
    {
        if (! $settings->get('quotations.qr_enabled', true)) {
            abort(404);
        }

        $quote = Quotation::query()->where('public_token', $token)->firstOrFail();
        $revision = $quote->currentRevision;

        // Minimal public fields only — never expose lines, notes, customer PII, phone, email
        return view('quotes.verify', [
            'number' => $quote->number,
            'revision' => $revision?->revision,
            'status' => $revision?->status,
            'grand_total' => $revision?->totals['grand_total'] ?? null,
            'currency' => $revision?->totals['currency'] ?? 'INR',
            'valid_until' => $revision?->valid_until?->toDateString(),
            'company_name' => $revision?->branding_snapshot['company_name']
                ?? $settings->get('branding.company_name', 'Solar CRM'),
        ]);
    }
}
