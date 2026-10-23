<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\VisitHistory;
use App\Services\AuditLogger;
use App\Services\NumberingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['reference'] = app(NumberingService::class)->next('visit');
        $data['created_by'] = $user?->id;
        $data['updated_by'] = $user?->id;
        $data['status'] = $data['status'] ?? 'scheduled';

        if ($user?->role?->slug === 'surveyor-engineer' && empty($data['assigned_surveyor_id'])) {
            $data['assigned_surveyor_id'] = $user->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        VisitHistory::query()->create([
            'visit_id' => $this->record->id,
            'event' => 'created',
            'from_status' => null,
            'to_status' => $this->record->status,
            'reason' => null,
            'changed_by' => Filament::auth()->id(),
            'created_at' => now(),
        ]);

        app(AuditLogger::class)->log(
            action: 'visit.created',
            entityType: $this->record::class,
            entityId: $this->record->id,
            entityReference: $this->record->reference,
            summary: ['status' => $this->record->status, 'surveyor' => $this->record->assigned_surveyor_id],
        );
    }
}
