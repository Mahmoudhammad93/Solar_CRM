<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationRevision;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class QuotationReportService
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly AuditLogger $auditLogger,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function generatePdf(Quotation $quote, ?QuotationRevision $revision = null): array
    {
        $revision ??= $quote->currentRevision;
        if (! $revision) {
            throw new RuntimeException('No revision to print.');
        }

        $filename = sprintf(
            'quotation-%s-r%s-%s.pdf',
            Str::slug($quote->number),
            $revision->revision,
            now()->format('Ymd-His'),
        );

        $qrEnabled = (bool) $this->settings->get('quotations.qr_enabled', true);
        $verifyUrl = url('/quotes/verify/'.$quote->public_token);
        $qrSvg = $qrEnabled ? $this->qrSvg($verifyUrl) : null;

        $pdf = Pdf::loadView('pdf.quotation', [
            'quote' => $quote,
            'revision' => $revision,
            'brand' => $revision->branding_snapshot ?: $this->branding->pdfSnapshot(),
            'logoDataUri' => $this->logoDataUri($revision->branding_snapshot['logo_path'] ?? $this->branding->logoPath()),
            'qrSvg' => $qrSvg,
            'verifyUrl' => $verifyUrl,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $binary = $pdf->output();
        $path = 'reports/quotations/'.$filename;
        Storage::disk('local')->put($path, $binary);

        $this->auditLogger->log(
            action: 'quotation.pdf_generated',
            entityType: Quotation::class,
            entityId: $quote->id,
            entityReference: $quote->number,
            summary: ['filename' => $filename, 'revision' => $revision->revision, 'bytes' => strlen($binary)],
        );

        return [$binary, $filename, Storage::disk('local')->path($path)];
    }

    public function qrSvg(string $url): string
    {
        $builder = new Builder(
            writer: new SvgWriter,
            data: $url,
            size: 160,
            margin: 4,
        );

        return $builder->build()->getString();
    }

    private function logoDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        $bytes = Storage::disk('public')->get($path);
        $mime = mime_content_type(Storage::disk('public')->path($path)) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
