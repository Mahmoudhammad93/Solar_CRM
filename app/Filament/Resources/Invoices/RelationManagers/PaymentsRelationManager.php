<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments (manual)';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')->numeric()->required()->minValue(0.01),
            Select::make('method')->options([
                'cash' => 'Cash',
                'bank_transfer' => 'Bank transfer',
                'upi' => 'UPI',
                'cheque' => 'Cheque',
                'other' => 'Other',
            ])->required(),
            DatePicker::make('paid_on')->required()->default(now()),
            TextInput::make('reference'),
            Textarea::make('notes'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_on')->date(),
                TextColumn::make('method'),
                TextColumn::make('reference'),
                TextColumn::make('amount')->money('INR'),
                TextColumn::make('recorder.name')->label('Recorded by'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Record payment')
                    ->using(function (array $data, RelationManager $livewire) {
                        /** @var Invoice $invoice */
                        $invoice = $livewire->getOwnerRecord();
                        try {
                            return app(InvoiceService::class)->recordPayment($invoice, $data, Filament::auth()->user());
                        } catch (ValidationException $e) {
                            Notification::make()->title('Payment rejected')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                            throw $e;
                        }
                    }),
            ]);
    }
}
