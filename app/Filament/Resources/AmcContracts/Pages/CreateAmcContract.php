<?php

namespace App\Filament\Resources\AmcContracts\Pages;

use App\Filament\Resources\AmcContracts\AmcContractResource;
use App\Services\AmcService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAmcContract extends CreateRecord
{
    protected static string $resource = AmcContractResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AmcService::class)->create($data, Filament::auth()->user());
    }
}
