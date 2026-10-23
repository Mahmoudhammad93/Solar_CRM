<?php

namespace App\Filament\Resources\AmcContracts\Pages;

use App\Filament\Resources\AmcContracts\AmcContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAmcContracts extends ListRecords
{
    protected static string $resource = AmcContractResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
