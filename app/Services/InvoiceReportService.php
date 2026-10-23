<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceReportService
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Branded invoice/receipt PDF.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function generate(Invoice $invoice): array
    {
        $invoice->loadMissing(['order', 'customer', 'payments.recorder']);
        $brand = $this->branding->pdfSnapshot();
        $filename = sprintf('invoice-%s-%s.pdf', Str::slug($invoice->number), now()->format('Ymd-His'));

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'brand' => $brand,
            'logoDataUri' => $this->logoDataUri(),
            'generatedAt' => now(),
            'outstanding' => $invoice->outstanding(),
            'paid' => $invoice->amountPaid(),
        ])->setPaper('a4');

        $binary = $pdf->output();
        $path = 'reports/invoices/'.$filename;
        Storage::disk('local')->put($path, $binary);

        $this->auditLogger->log(
            action: 'invoice.report_generated',
            entityType: Invoice::class,
            entityId: $invoice->id,
            entityReference: $invoice->number,
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
