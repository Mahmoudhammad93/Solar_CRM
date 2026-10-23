<?php

namespace App\Filament\Resources\AmcContracts\Pages;

use App\Filament\Resources\AmcContracts\AmcContractResource;
use App\Services\AmcService;
use App\Support\AmcStatuses;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAmcContract extends EditRecord
{
    protected static string $resource = AmcContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->form([
                    Select::make('status')->options(AmcStatuses::labels())->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        app(AmcService::class)->transition($this->record, $data['status'], Filament::auth()->user());
                        Notification::make()->title('AMC status updated')->success()->send();
                        $this->record->refresh();
                        $this->refreshFormData(['status']);
                    } catch (ValidationException $e) {
                        Notification::make()->title('Blocked')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    }
                }),
        ];
    }
}
