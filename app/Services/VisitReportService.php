<?php

namespace App\Services;

use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VisitReportService
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Generate PDF bytes and return [binary, filename, absolutePath].
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function generate(Visit $visit): array
    {
        $visit->loadMissing(['lead', 'customer', 'surveyor']);
        $brand = $this->branding->pdfSnapshot();
        $filename = sprintf(
            'visit-report-%s-%s.pdf',
            Str::slug($visit->reference),
            now()->format('Ymd-His'),
        );

        $pdf = Pdf::loadView('pdf.visit-report', [
            'visit' => $visit,
            'brand' => $brand,
            'logoDataUri' => $this->logoDataUri(),
            'generatedAt' => now(),
        ])->setPaper('a4');

        $binary = $pdf->output();
        $path = 'reports/visits/'.$filename;
        Storage::disk('local')->put($path, $binary);

        $this->auditLogger->log(
            action: 'visit.report_generated',
            entityType: Visit::class,
            entityId: $visit->id,
            entityReference: $visit->reference,
            summary: [
                'filename' => $filename,
                'bytes' => strlen($binary),
            ],
        );

        return [$binary, $filename, Storage::disk('local')->path($path)];
    }

    private function logoDataUri(): ?string
    {
        $path = $this->branding->logoPath();
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($path);
        $mime = mime_content_type(Storage::disk('public')->path($path)) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
