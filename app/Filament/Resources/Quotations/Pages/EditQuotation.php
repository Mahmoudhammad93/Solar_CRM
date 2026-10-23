<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Customer;
use App\Services\ConvertQuotationToProject;
use App\Services\MessageService;
use App\Services\QuotationReportService;
use App\Services\QuotationService;
use App\Support\QuotationWorkflow;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $rev = $this->record->currentRevision;
        if ($rev) {
            $data = array_merge($data, [
                'customer_state' => $rev->customer_state,
                'site_address' => $rev->site_address,
                'system_type' => $rev->system_type,
                'system_capacity_kw' => $rev->system_capacity_kw,
                'advance_amount' => $rev->advance_amount,
                'internal_notes' => $rev->internal_notes,
                'customer_terms' => $rev->customer_terms,
                'pump_details' => $rev->pump_details,
                'lines' => $rev->lines ?? [],
                'number' => $this->record->number,
            ]);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $svc = app(QuotationService::class);
        $lines = $data['lines'] ?? [];
        if (empty($data['customer_state']) && ! empty($data['customer_id'])) {
            $data['customer_state'] = Customer::query()->whereKey($data['customer_id'])->value('state');
        }

        $header = $data;
        unset($header['lines'], $header['number']);

        $record->fill([
            'proposal_type' => $data['proposal_type'] ?? $record->proposal_type,
            'owner_id' => $data['owner_id'] ?? $record->owner_id,
            'lead_id' => $data['lead_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'visit_id' => $data['visit_id'] ?? null,
            'calculation_run_id' => $data['calculation_run_id'] ?? $record->calculation_run_id,
        ])->save();

        $rev = $record->currentRevision;
        try {
            if ($rev && $rev->isEditable()) {
                $svc->updateDraft($record->fresh(), $header, $lines, Filament::auth()->user());
            } else {
                $svc->revise($record->fresh(), $header, $lines, Filament::auth()->user());
                Notification::make()->title('Locked revision — created a new draft revision')->warning()->send();
            }
        } catch (ValidationException $e) {
            Notification::make()->title('Save failed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
            throw $e;
        }

        return $record->fresh(['currentRevision']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->form([
                    Select::make('status')
                        ->options(QuotationWorkflow::labels())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        app(QuotationService::class)->transition($this->record, $data['status'], Filament::auth()->user());
                        Notification::make()->title('Status updated')->success()->send();
                        $this->refreshFormData(['lines']);
                        $this->record->refresh();
                    } catch (ValidationException $e) {
                        Notification::make()->title('Transition blocked')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    }
                }),
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->action(function (): StreamedResponse {
                    [$binary, $filename] = app(QuotationReportService::class)->generatePdf($this->record);

                    return response()->streamDownload(fn () => print ($binary), $filename, ['Content-Type' => 'application/pdf']);
                }),
            Action::make('shareSimulated')
                ->label('Share WhatsApp (SIMULATED – not delivered)')
                ->form([
                    TextInput::make('to')
                        ->label('Recipient phone (with country code)')
                        ->default(fn () => $this->record->customer?->mobile)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $customer = $this->record->customer;
                    $result = app(MessageService::class)->share('whatsapp', 'quotation_ready', [
                        'entity_type' => $this->record::class,
                        'entity_id' => $this->record->id,
                        'entity_reference' => $this->record->number,
                        'to' => $data['to'],
                        'customer_id' => $customer?->id,
                        'customer_name' => $customer?->name,
                        'quote_number' => $this->record->number,
                        'link' => route('quotes.verify', $this->record->public_token),
                    ], Filament::auth()->user());
                    $n = Notification::make()->title($result['message']);
                    ($result['simulated'] ?? false) ? $n->warning()->send() : $n->danger()->send();
                }),
            Action::make('openWhatsApp')
                ->label('Open conversation (opens WhatsApp, no delivery tracking)')
                ->url(function (): ?string {
                    $mobile = $this->record->customer?->mobile;
                    if (! filled($mobile)) {
                        return null;
                    }
                    try {
                        return app(MessageService::class)->openConversation($mobile)['url'];
                    } catch (\Throwable) {
                        return null;
                    }
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->record->customer?->mobile)),
            Action::make('captureSignature')
                ->label('Attach demo signature')
                ->form([
                    FileUpload::make('signature')
                        ->image()
                        ->disk('public')
                        ->directory('signatures')
                        ->required()
                        ->helperText('Demo e-signature, not legally verified'),
                ])
                ->action(function (array $data): void {
                    Gate::authorize('update', $this->record);
                    $path = is_array($data['signature']) ? ($data['signature'][0] ?? null) : $data['signature'];
                    app(QuotationService::class)->attachSignature($this->record->currentRevision, (string) $path, Filament::auth()->user());
                    Notification::make()->title('Signature stored (demo)')->success()->send();
                }),
            Action::make('printView')
                ->url(fn () => route('quotes.verify', $this->record->public_token))
                ->openUrlInNewTab()
                ->label('Public verify page'),
            Action::make('convertToProject')
                ->label('Convert to order/project')
                ->color('success')
                ->visible(fn () => strtolower((string) $this->record->currentRevision?->status) === 'accepted')
                ->requiresConfirmation()
                ->modalHeading('Convert accepted quotation')
                ->modalDescription('Creates linked order + project from the locked revision snapshot. Safe to retry — duplicates are blocked.')
                ->action(function (): void {
                    try {
                        $result = app(ConvertQuotationToProject::class)->convert($this->record, Filament::auth()->user());
                        Notification::make()
                            ->title($result['created'] ? 'Order/project created' : 'Already converted')
                            ->body($result['message'].' Order '.$result['order']->number.' · Project '.$result['project']->number)
                            ->success()
                            ->send();
                        $this->record->refresh();
                    } catch (ValidationException $e) {
                        Notification::make()->title('Conversion blocked')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    }
                }),
        ];
    }
}
