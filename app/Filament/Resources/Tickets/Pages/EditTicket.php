<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\User;
use App\Services\TicketReportService;
use App\Services\TicketService;
use App\Support\TicketWorkflow;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign')
                ->form([
                    Select::make('assignee_id')
                        ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    $assignee = User::query()->findOrFail($data['assignee_id']);
                    app(TicketService::class)->assign($this->record, $assignee, Filament::auth()->user());
                    Notification::make()->title('Assigned')->success()->send();
                    $this->refreshFormData(['status', 'assignee_id']);
                    $this->record->refresh();
                }),
            Action::make('changeStatus')
                ->form([
                    Select::make('status')->options(TicketWorkflow::labels())->required(),
                    Textarea::make('notes')->label('Notes / resolution'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(TicketService::class)->transition($this->record, $data['status'], Filament::auth()->user(), $data['notes'] ?? null);
                        Notification::make()->title('Status updated')->success()->send();
                        $this->record->refresh();
                        $this->refreshFormData(['status', 'resolution_notes']);
                    } catch (ValidationException $e) {
                        Notification::make()->title('Blocked')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    }
                }),
            Action::make('addComment')
                ->form([
                    Textarea::make('body')->required(),
                    Toggle::make('is_internal')->default(true)->label('Internal note'),
                ])
                ->action(function (array $data): void {
                    app(TicketService::class)->addComment($this->record, $data['body'], Filament::auth()->user(), (bool) ($data['is_internal'] ?? true));
                    Notification::make()->title('Comment added')->success()->send();
                }),
            Action::make('downloadReport')
                ->label('Resolution PDF')
                ->action(function (): StreamedResponse {
                    [$binary, $filename] = app(TicketReportService::class)->generate($this->record);

                    return response()->streamDownload(fn () => print ($binary), $filename, ['Content-Type' => 'application/pdf']);
                }),
        ];
    }
}
