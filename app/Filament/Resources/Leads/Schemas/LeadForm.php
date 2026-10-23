<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Branch;
use App\Models\Lookup;
use App\Models\User;
use App\Services\LeadDuplicateChecker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->columns(2)->schema([
                TextInput::make('reference')->label('Lead ID')->disabled()->dehydrated(false)
                    ->placeholder('Auto-generated on save')
                    ->visibleOn('edit'),
                TextInput::make('title')->label('Lead title')->maxLength(255),
                Select::make('customer_type')->options(fn (Get $get) => Lookup::options('customer_type', $get('customer_type')))->searchable(),
                TextInput::make('name')->label('Full name / company contact name')->required()->maxLength(255),
                TextInput::make('company_name')->label('Company')->maxLength(255),
                TextInput::make('contact_person')->maxLength(255),
                TextInput::make('mobile')->tel()->required()->live(onBlur: true)->maxLength(50),
                TextInput::make('alternate_phone')->tel()->maxLength(50),
                TextInput::make('email')->email()->live(onBlur: true)->maxLength(255),
                Placeholder::make('duplicate_warning')
                    ->label('Duplicate check')
                    ->content(function (Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                        $checker = app(LeadDuplicateChecker::class);
                        $hits = $checker->findDuplicates($get('mobile'), $get('email'), $record?->getKey());
                        if ($hits->isEmpty()) {
                            return new HtmlString('<span class="text-sm text-gray-500">No matching phone/email leads found.</span>');
                        }
                        $links = $hits->map(function ($lead) {
                            $url = \App\Filament\Resources\Leads\LeadResource::getUrl('edit', ['record' => $lead]);

                            return '<a class="text-primary-600 underline" href="'.e($url).'">'.e($lead->reference).' — '.e($lead->name).'</a>';
                        })->implode('<br>');

                        return new HtmlString('<div class="text-sm text-warning-600 font-medium">Possible duplicates:</div>'.$links);
                    })
                    ->columnSpanFull(),
            ]),
            Section::make('Location')->columns(2)->schema([
                Textarea::make('address')->rows(2)->columnSpanFull(),
                TextInput::make('city'),
                TextInput::make('district'),
                TextInput::make('state'),
                TextInput::make('postal_code')->label('PIN / postal code'),
                TextInput::make('gps_lat')->label('GPS latitude')->numeric(),
                TextInput::make('gps_lng')->label('GPS longitude')->numeric(),
                Select::make('branch_id')->label('Branch')->options(fn () => Branch::query()->where('is_active', true)->pluck('name', 'id'))->searchable(),
                TextInput::make('territory'),
            ]),
            Section::make('Solar need')->columns(2)->schema([
                Select::make('system_type')->options(fn (Get $get) => Lookup::options('system_type', $get('system_type')))->searchable(),
                Select::make('property_segment')->options(fn (Get $get) => Lookup::options('property_segment', $get('property_segment')))->searchable(),
                Select::make('roof_site_type')->label('Roof / site type')->options(fn (Get $get) => Lookup::options('property_type', $get('roof_site_type')))->searchable(),
                TextInput::make('monthly_bill')->numeric()->prefix('EGP'),
                TextInput::make('monthly_units')->numeric()->suffix('kWh'),
                TextInput::make('sanctioned_load_kw')->label('Sanctioned load (kW)')->numeric(),
                TextInput::make('desired_capacity_kw')->label('Desired capacity (kW)')->numeric(),
                Select::make('priority')->options(fn (Get $get) => Lookup::options('priority', $get('priority')))->searchable(),
                Select::make('budget_range')->options(fn (Get $get) => Lookup::options('budget_range', $get('budget_range')))->searchable(),
            ]),
            Section::make('Sales tracking')->columns(2)->schema([
                Select::make('source')->options(fn (Get $get) => Lookup::options('lead_source', $get('source')))->searchable(),
                TextInput::make('campaign_details')->label('Campaign / source details'),
                Select::make('assigned_to')
                    ->label('Owner')
                    ->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('stage')
                    ->options(fn (Get $get) => Lookup::options('lead_stage', $get('stage')))
                    ->searchable()
                    ->required()
                    ->live(),
                Select::make('loss_reason')
                    ->options(fn (Get $get) => Lookup::options('loss_reason', $get('loss_reason')))
                    ->searchable()
                    ->visible(fn (Get $get) => strtolower((string) $get('stage')) === 'lost')
                    ->required(fn (Get $get) => strtolower((string) $get('stage')) === 'lost'),
                TextInput::make('probability')->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                TextInput::make('expected_value')->numeric()->prefix('EGP'),
                TagsInput::make('tags'),
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Follow-up')->columns(2)->schema([
                DateTimePicker::make('next_follow_up_at')->label('Next follow-up')->seconds(false),
                DateTimePicker::make('reminder_at')->label('Reminder')->seconds(false),
                TextInput::make('follow_up_outcome')->label('Follow-up outcome')->columnSpanFull(),
            ]),
        ]);
    }
}
