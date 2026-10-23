<?php

namespace App\Services;

use App\Services\Reports\ReportQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportExportService
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * UTF-8 BOM CSV for Excel compatibility. Same rows as ReportQuery::rows().
     */
    public function toCsv(ReportQuery $report): string
    {
        $rows = $report->rows();
        $columns = $report->columns();
        $fh = fopen('php://temp', 'r+');
        // UTF-8 BOM
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, array_column($columns, 'label'));
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $row[$col['key']] ?? '';
            }
            fputcsv($fh, $line);
        }
        $totals = $report->totals();
        if ($totals !== []) {
            fputcsv($fh, []);
            foreach ($totals as $k => $v) {
                fputcsv($fh, [$k, $v]);
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        $this->auditLogger->log(
            action: 'report.exported_csv',
            entityType: ReportQuery::class,
            entityReference: $report->key(),
            summary: ['rows' => $rows->count(), 'totals' => $totals],
        );

        return $csv;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function toPdf(ReportQuery $report): array
    {
        $rows = $report->rows();
        $filename = sprintf('report-%s-%s.pdf', Str::slug($report->key()), now()->format('Ymd-His'));
        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report,
            'rows' => $rows,
            'totals' => $report->totals(),
            'brand' => $this->branding->pdfSnapshot(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $binary = $pdf->output();
        Storage::disk('local')->put('reports/exports/'.$filename, $binary);

        $this->auditLogger->log(
            action: 'report.exported_pdf',
            entityType: ReportQuery::class,
            entityReference: $report->key(),
            summary: ['rows' => $rows->count(), 'filename' => $filename],
        );

        return [$binary, $filename];
    }
}
