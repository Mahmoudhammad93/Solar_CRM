<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Services\NumberingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['reference'] = app(NumberingService::class)->next('task');
        $data['created_by'] = $user?->id;
        $data['updated_by'] = $user?->id;
        $data['owner_id'] = $data['owner_id'] ?? $user?->id;
        $data['status'] = $data['status'] ?? 'open';

        if (($data['status'] ?? null) === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = now();
        }

        return $data;
    }
}
