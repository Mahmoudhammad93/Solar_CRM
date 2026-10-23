<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Support\RecordScoping;
use App\Support\TicketWorkflow;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Service';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')->columns(2)->schema([
                TextInput::make('number')->disabled()->dehydrated(false),
                Select::make('status')->options(TicketWorkflow::labels())->disabled(),
                Select::make('customer_id')->label('Customer')->options(fn () => Customer::query()->orderBy('name')->pluck('name', 'id'))->searchable()->required(),
                Select::make('project_id')->label('Project')->options(fn () => Project::query()->orderByDesc('id')->pluck('number', 'id'))->searchable(),
                Select::make('order_id')->relationship('order', 'number')->searchable(),
                Select::make('product_id')->relationship('product', 'sku')->searchable(),
                TextInput::make('category'),
                Select::make('priority')->options([
                    'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent',
                ]),
                TextInput::make('source'),
                Select::make('assignee_id')->label('Assignee')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable(),
                TextInput::make('issue_summary')->columnSpanFull()->required(),
                Textarea::make('issue_detail')->columnSpanFull(),
                Textarea::make('resolution_notes')->columnSpanFull(),
                Textarea::make('feedback')->columnSpanFull(),
                TextInput::make('target_resolution_at')->disabled()->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('customer.name')->searchable(),
                TextColumn::make('project.number')->label('Project')->toggleable(),
                TextColumn::make('issue_summary')->limit(40)->searchable(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('assignee.name')->toggleable(),
                TextColumn::make('target_resolution_at')->dateTime()->sortable(),
                IconColumn::make('overdue')->boolean()->getStateUsing(fn (Ticket $r) => $r->isOverdue()),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(TicketWorkflow::labels()),
                SelectFilter::make('priority')->options([
                    'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent',
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return RecordScoping::applyAssignedScope(
            parent::getEloquentQuery()->with(['customer', 'project', 'assignee']),
            Filament::auth()->user(),
            'assignee_id',
        );
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasPermission('tickets.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->hasPermission('tickets.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && $user->hasPermission('tickets.edit')
            && RecordScoping::canAccessOwnedRecord($user, $record->assignee_id ?? $record->created_by);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
