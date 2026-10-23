<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\LeadStageHistory;
use App\Services\AuditLogger;
use App\Services\NumberingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['reference'] = app(NumberingService::class)->next('lead');
        $data['created_by'] = $user?->id;
        $data['updated_by'] = $user?->id;
        $data['stage'] = $data['stage'] ?? 'new';
        $data['assigned_to'] = $data['assigned_to'] ?? $user?->id;

        if (strtolower((string) ($data['stage'] ?? '')) !== 'lost') {
            $data['loss_reason'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        LeadStageHistory::query()->create([
            'lead_id' => $this->record->id,
            'from_stage' => null,
            'to_stage' => $this->record->stage ?? 'new',
            'changed_by' => Filament::auth()->id(),
            'notes' => 'Lead created',
            'created_at' => now(),
        ]);

        app(AuditLogger::class)->log(
            action: 'lead.created',
            entityType: $this->record::class,
            entityId: $this->record->id,
            entityReference: $this->record->reference,
            summary: ['name' => $this->record->name, 'stage' => $this->record->stage],
        );
    }
}
