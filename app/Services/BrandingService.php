<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class BrandingService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function appName(): string
    {
        return (string) $this->settings->get('branding.app_name', config('app.name', 'Solar CRM'));
    }

    public function companyName(): string
    {
        return (string) $this->settings->get('branding.company_name', $this->appName());
    }

    public function legalName(): string
    {
        return (string) $this->settings->get('branding.legal_name', $this->companyName());
    }

    public function footerText(): string
    {
        return (string) $this->settings->get('branding.footer_text', $this->companyName());
    }

    public function accentColor(): string
    {
        return (string) $this->settings->get('branding.accent_color', '#F59E0B');
    }

    public function logoPath(): ?string
    {
        $path = $this->settings->get('branding.logo_path');

        return $path ? (string) $path : null;
    }

    public function faviconPath(): ?string
    {
        $path = $this->settings->get('branding.favicon_path');

        return $path ? (string) $path : null;
    }

    public function loginLogoPath(): ?string
    {
        $path = $this->settings->get('branding.login_logo_path');

        return $path ? (string) $path : $this->logoPath();
    }

    public function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrl($this->logoPath());
    }

    public function loginLogoUrl(): ?string
    {
        return $this->publicUrl($this->loginLogoPath());
    }

    public function faviconUrl(): ?string
    {
        return $this->publicUrl($this->faviconPath());
    }

    /**
     * Snapshot for PDF/document layers in later phases.
     *
     * @return array<string, mixed>
     */
    public function pdfSnapshot(): array
    {
        return [
            'app_name' => $this->appName(),
            'company_name' => $this->companyName(),
            'legal_name' => $this->legalName(),
            'address' => $this->settings->get('branding.address'),
            'phone' => $this->settings->get('branding.phone'),
            'support_email' => $this->settings->get('branding.support_email'),
            'website' => $this->settings->get('branding.website'),
            'gstin' => $this->settings->get('branding.gstin'),
            'invoice_address' => $this->settings->get('branding.invoice_address'),
            'footer_text' => $this->footerText(),
            'accent_color' => $this->accentColor(),
            'logo_path' => $this->logoPath(),
            'captured_at' => now()->toIso8601String(),
        ];
    }
}
