<?php

namespace App\Services;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketReportService
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function generate(Ticket $ticket): array
    {
        $ticket->loadMissing(['customer', 'project', 'order', 'assignee', 'statusHistories.user', 'comments.user']);
        $brand = $this->branding->pdfSnapshot();
        $filename = sprintf('ticket-resolution-%s-%s.pdf', Str::slug($ticket->number), now()->format('Ymd-His'));

        $pdf = Pdf::loadView('pdf.ticket-resolution', [
            'ticket' => $ticket,
            'brand' => $brand,
            'logoDataUri' => $this->logoDataUri(),
            'generatedAt' => now(),
        ])->setPaper('a4');

        $binary = $pdf->output();
        $path = 'reports/tickets/'.$filename;
        Storage::disk('local')->put($path, $binary);

        $this->auditLogger->log(
            action: 'ticket.report_generated',
            entityType: Ticket::class,
            entityId: $ticket->id,
            entityReference: $ticket->number,
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
