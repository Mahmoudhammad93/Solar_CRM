<?php

namespace App\Services;

use App\Models\CalculationRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CalculationReportService
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{0: string, 1: string, 2: string} binary, filename, absolutePath
     */
    public function generate(CalculationRun $run): array
    {
        if ($run->calculator_type !== 'hybrid') {
            throw new RuntimeException('PDF report is implemented for hybrid runs only in Phase 5.');
        }

        $brand = $this->branding->pdfSnapshot();
        $filename = sprintf(
            'hybrid-calc-%s-%s.pdf',
            Str::slug($run->reference),
            now()->format('Ymd-His'),
        );

        $pdf = Pdf::loadView('pdf.hybrid-calculation', [
            'run' => $run,
            'brand' => $brand,
            'logoDataUri' => $this->logoDataUri(),
            'generatedAt' => now(),
        ])->setPaper('a4');

        $binary = $pdf->output();
        $path = 'reports/calculations/'.$filename;
        Storage::disk('local')->put($path, $binary);

        $this->auditLogger->log(
            action: 'calculation.report_generated',
            entityType: CalculationRun::class,
            entityId: $run->id,
            entityReference: $run->reference,
            summary: ['filename' => $filename, 'bytes' => strlen($binary)],
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
