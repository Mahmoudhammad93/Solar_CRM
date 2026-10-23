<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use App\Models\Invoice;
use App\Support\RecordScoping;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice')->columns(2)->schema([
                TextInput::make('number')->disabled(),
                TextInput::make('status')->disabled(),
                Placeholder::make('order_ref')->label('Order')->content(fn (?Invoice $r) => $r?->order?->number),
                Placeholder::make('customer_ref')->label('Customer')->content(fn (?Invoice $r) => $r?->customer?->name),
                TextInput::make('subtotal')->disabled(),
                TextInput::make('tax_total')->disabled(),
                TextInput::make('grand_total')->disabled(),
                Placeholder::make('paid')->label('Paid (stored)')
                    ->content(fn (?Invoice $r) => $r ? number_format($r->amountPaid(), 2) : '—'),
                Placeholder::make('outstanding')->label('Outstanding')
                    ->content(fn (?Invoice $r) => $r ? number_format($r->outstanding(), 2) : '—'),
                Placeholder::make('gateway')
                    ->label('Payment gateway')
                    ->content(fn (?Invoice $r) => $r?->payment_gateway_note ?? 'Simulated/not implemented')
                    ->columnSpanFull(),
                Placeholder::make('schedule')
                    ->label('Payment schedule (from order)')
                    ->content(fn (?Invoice $r) => new HtmlString('<pre style="font-size:11px">'.e(json_encode($r?->schedule_snapshot, JSON_PRETTY_PRINT)).'</pre>'))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('order.number')->label('Order')->searchable(),
                TextColumn::make('customer.name')->searchable()->toggleable(),
                TextColumn::make('grand_total')->money('INR'),
                TextColumn::make('paid')->label('Paid')
                    ->getStateUsing(fn (Invoice $r) => $r->amountPaid())
                    ->money('INR'),
                TextColumn::make('outstanding')->label('Outstanding')
                    ->getStateUsing(fn (Invoice $r) => $r->outstanding())
                    ->money('INR'),
                TextColumn::make('status')->badge(),
                TextColumn::make('invoiced_on')->date()->sortable(),
            ])
            ->defaultSort('invoiced_on', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'issued' => 'Issued',
                    'partial' => 'Partial',
                    'paid' => 'Paid',
                    'void' => 'Void',
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['order', 'customer', 'payments']);
        $user = Filament::auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        if (RecordScoping::mustScopeToAssigned($user) || RecordScoping::isSalesManager($user)) {
            $query->whereHas('order', function (Builder $q) use ($user) {
                RecordScoping::applyAssignedScope($q, $user, 'owner_id');
            });
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        $u = Filament::auth()->user();

        return $u?->hasPermission('orders.view') || $u?->hasPermission('reports.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false; // via order action
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
