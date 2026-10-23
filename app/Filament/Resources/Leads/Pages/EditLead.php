<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\LeadStageHistory;
use App\Services\AuditLogger;
use App\Services\LeadConversionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected ?string $stageBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('convert')
                ->label('Convert to customer')
                ->color('success')
                ->visible(fn (): bool => ! $this->record->isConverted() && ! $this->record->isLost())
                ->requiresConfirmation()
                ->action(function (): void {
                    $customer = app(LeadConversionService::class)->convert($this->record, Filament::auth()->user());
                    $this->record->refresh();
                    Notification::make()
                        ->title('Converted to '.$customer->customer_number)
                        ->success()
                        ->send();
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
        $this->stageBeforeSave = $this->record->stage;
        $data['updated_by'] = Filament::auth()->id();

        if (strtolower((string) ($data['stage'] ?? '')) === 'lost' && blank($data['loss_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'data.loss_reason' => 'Loss reason is required when stage is Lost.',
            ]);
        }

        if (strtolower((string) ($data['stage'] ?? '')) !== 'lost') {
            $data['loss_reason'] = null;
        }

        $user = Filament::auth()->user();
        $canAssign = $user?->isSuperAdmin() || $user?->role?->slug === 'sales-manager';
        if (! $canAssign && array_key_exists('assigned_to', $data)) {
            $data['assigned_to'] = $this->record->assigned_to;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->stageBeforeSave !== $this->record->stage) {
            LeadStageHistory::query()->create([
                'lead_id' => $this->record->id,
                'from_stage' => $this->stageBeforeSave,
                'to_stage' => $this->record->stage,
                'changed_by' => Filament::auth()->id(),
                'notes' => 'Stage changed',
                'created_at' => now(),
            ]);
        }

        app(AuditLogger::class)->log(
            action: 'lead.updated',
            entityType: $this->record::class,
            entityId: $this->record->id,
            entityReference: $this->record->reference,
            summary: [
                'stage' => $this->record->stage,
                'assigned_to' => $this->record->assigned_to,
            ],
        );
    }
}
