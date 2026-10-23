<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Support\DashboardKpiQuery;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getTableQuery(): Builder|Relation|null
    {
        return DashboardKpiQuery::applyLeads(parent::getTableQuery());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => Filament::auth()->user()?->can('export', Lead::class)
                    || Filament::auth()->user()?->hasPermission('leads.export')
                    || Filament::auth()->user()?->hasPermission('leads.view'))
                ->action(fn () => $this->exportFilteredCsv()),
            CreateAction::make(),
        ];
    }

    protected function exportFilteredCsv(): StreamedResponse
    {
        $query = $this->getFilteredTableQuery();
        $filename = 'leads-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'reference', 'title', 'name', 'mobile', 'email', 'city', 'source', 'stage',
                'owner', 'priority', 'next_follow_up_at', 'expected_value', 'created_at',
            ]);

            $query->with('assignee')->orderBy('id')->chunk(200, function ($leads) use ($out): void {
                foreach ($leads as $lead) {
                    fputcsv($out, [
                        $lead->reference,
                        $lead->title,
                        $lead->name,
                        $lead->mobile,
                        $lead->email,
                        $lead->city,
                        $lead->source,
                        $lead->stage,
                        $lead->assignee?->name,
                        $lead->priority,
                        optional($lead->next_follow_up_at)?->toDateTimeString(),
                        $lead->expected_value,
                        optional($lead->created_at)?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
