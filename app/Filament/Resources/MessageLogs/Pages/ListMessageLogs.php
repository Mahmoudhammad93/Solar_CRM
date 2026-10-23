<?php

namespace App\Filament\Resources\MessageLogs\Pages;

use App\Filament\Resources\MessageLogs\MessageLogResource;
use Filament\Resources\Pages\ListRecords;

class ListMessageLogs extends ListRecords
{
    protected static string $resource = MessageLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Statuses are honest: Demo logs show “SIMULATED – not delivered”. Never queued/sent/delivered without a real provider response.';
    }
}
