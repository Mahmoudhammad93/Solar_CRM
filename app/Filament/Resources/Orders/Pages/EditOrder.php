<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')->columns(2)->schema([
                TextInput::make('number')->disabled(),
                TextInput::make('status')->disabled(),
                Placeholder::make('quote_link')
                    ->label('Source quotation')
                    ->content(fn () => $this->record->quotation?->number.' (rev '.$this->record->quotationRevision?->revision.')'),
                Placeholder::make('project_link')
                    ->label('Linked project')
                    ->content(fn () => $this->record->project?->number ?? '—'),
                TextInput::make('site_address')->columnSpanFull(),
                TextInput::make('project_type'),
                TextInput::make('capacity_kw')->numeric(),
                TextInput::make('contract_value')->numeric()->disabled(),
                Textarea::make('confirmed_scope')->columnSpanFull(),
                TextInput::make('delivery_target'),
                Textarea::make('cancellation_reason')->columnSpanFull(),
                Placeholder::make('payment_schedule')
                    ->label('Payment schedule (from quote snapshot)')
                    ->content(fn () => new HtmlString('<pre style="font-size:11px">'.e(json_encode($this->record->payment_schedule, JSON_PRETTY_PRINT)).'</pre>'))
                    ->columnSpanFull(),
                Placeholder::make('timeline')
                    ->label('Snapshot totals')
                    ->content(fn () => new HtmlString('<pre style="font-size:11px">'.e(json_encode($this->record->totals_snapshot, JSON_PRETTY_PRINT)).'</pre>'))
                    ->columnSpanFull(),
                Placeholder::make('docs_note')
                    ->label('Documents')
                    ->content('Attach via Documents module (morph to Order). Related project documents live on the Project record.')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
