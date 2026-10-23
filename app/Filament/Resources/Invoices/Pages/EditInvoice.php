<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Invoice/Receipt PDF')
                ->action(function (): StreamedResponse {
                    [$binary, $filename] = app(InvoiceReportService::class)->generate($this->record);

                    return response()->streamDownload(fn () => print ($binary), $filename, ['Content-Type' => 'application/pdf']);
                }),
        ];
    }
}
