<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Lookup;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Task')->columns(2)->schema([
                TextInput::make('reference')->disabled()->dehydrated(false)->visibleOn('edit'),
                TextInput::make('title')->required()->columnSpanFull(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                Select::make('task_type')->options(fn (Get $get) => Lookup::options('task_type', $get('task_type'))),
                Select::make('priority')->options(fn (Get $get) => Lookup::options('priority', $get('priority'))),
                Select::make('status')->options(fn (Get $get) => Lookup::options('task_status', $get('status')))->required(),
                Select::make('owner_id')
                    ->label('Owner')
                    ->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                DateTimePicker::make('due_at')->seconds(false),
                DateTimePicker::make('reminder_at')->seconds(false),
                TextInput::make('outcome'),
                DateTimePicker::make('completed_at')->seconds(false),
                Select::make('taskable_type')
                    ->label('Linked record type')
                    ->options([
                        Lead::class => 'Lead',
                        Customer::class => 'Customer',
                        // Stubs for later modules:
                        'App\\Models\\Visit' => 'Visit (later)',
                        'App\\Models\\Quotation' => 'Quote (later)',
                        'App\\Models\\Project' => 'Project (later)',
                        'App\\Models\\Ticket' => 'Ticket (later)',
                    ])
                    ->live()
                    ->searchable(),
                Select::make('taskable_id')
                    ->label('Linked record')
                    ->options(function (Get $get) {
                        return match ($get('taskable_type')) {
                            Lead::class => Lead::query()->orderByDesc('id')->limit(100)->pluck('reference', 'id'),
                            Customer::class => Customer::query()->orderByDesc('id')->limit(100)->pluck('customer_number', 'id'),
                            default => [],
                        };
                    })
                    ->searchable()
                    ->visible(fn (Get $get) => filled($get('taskable_type')) && in_array($get('taskable_type'), [Lead::class, Customer::class], true)),
            ]),
        ]);
    }
}
