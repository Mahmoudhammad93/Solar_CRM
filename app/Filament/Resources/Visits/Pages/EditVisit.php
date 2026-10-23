<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\VisitHistory;
use App\Services\AuditLogger;
use App\Services\VisitReportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditVisit extends EditRecord
{
    protected static string $resource = VisitResource::class;

    protected ?string $statusBefore = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadReport')
                ->label('Download visit report PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (): StreamedResponse {
                    Gate::authorize('downloadReport', $this->record);
                    [$binary, $filename] = app(VisitReportService::class)->generate($this->record);

                    return response()->streamDownload(function () use ($binary): void {
                        echo $binary;
                    }, $filename, ['Content-Type' => 'application/pdf']);
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->statusBefore = $this->record->status;
        $data['updated_by'] = Filament::auth()->id();

        $status = strtolower((string) ($data['status'] ?? ''));
        if (in_array($status, ['rescheduled', 'cancelled'], true) && blank($data['reschedule_cancel_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'data.reschedule_cancel_reason' => 'A reason is required when rescheduling or cancelling.',
            ]);
        }

        if ($status === 'completed' && empty($data['completed_at'] ?? null) && ! $this->record->completed_at) {
            $data['completed_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $from = $this->statusBefore;
        $to = $this->record->status;

        if ($from !== $to || filled($this->record->reschedule_cancel_reason)) {
            $event = match (strtolower((string) $to)) {
                'rescheduled' => 'rescheduled',
                'cancelled' => 'cancelled',
                'completed' => 'completed',
                default => 'status_changed',
            };

            VisitHistory::query()->create([
                'visit_id' => $this->record->id,
                'event' => $event,
                'from_status' => $from,
                'to_status' => $to,
                'reason' => $this->record->reschedule_cancel_reason,
                'changed_by' => Filament::auth()->id(),
                'created_at' => now(),
            ]);
        }

        app(AuditLogger::class)->log(
            action: 'visit.updated',
            entityType: $this->record::class,
            entityId: $this->record->id,
            entityReference: $this->record->reference,
            summary: ['status' => $to, 'from' => $from],
        );

        Notification::make()->title('Visit saved')->success()->send();
    }
}
