<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Lookup;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visit')->columns(2)->schema([
                TextInput::make('reference')->disabled()->dehydrated(false)->visibleOn('edit'),
                Select::make('lead_id')
                    ->label('Linked lead')
                    ->options(fn () => Lead::query()->orderByDesc('id')->limit(200)->get()->mapWithKeys(fn ($l) => [$l->id => $l->reference.' — '.$l->name]))
                    ->searchable(),
                Select::make('customer_id')
                    ->label('Linked customer')
                    ->options(fn () => Customer::query()->orderByDesc('id')->limit(200)->get()->mapWithKeys(fn ($c) => [$c->id => $c->customer_number.' — '.$c->name]))
                    ->searchable(),
                DateTimePicker::make('scheduled_at')->label('Schedule')->seconds(false)->required(),
                Select::make('assigned_surveyor_id')
                    ->label('Assigned surveyor')
                    ->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->options(fn (Get $get) => Lookup::options('visit_status', $get('status')))
                    ->required()
                    ->live(),
                Textarea::make('reschedule_cancel_reason')
                    ->label('Reschedule / cancel reason')
                    ->visible(fn (Get $get) => in_array(strtolower((string) $get('status')), ['rescheduled', 'cancelled'], true))
                    ->required(fn (Get $get) => in_array(strtolower((string) $get('status')), ['rescheduled', 'cancelled'], true))
                    ->columnSpanFull(),
                TextInput::make('site_address')->columnSpanFull(),
                TextInput::make('city'),
                TextInput::make('state'),
                TextInput::make('postal_code'),
                TextInput::make('gps_lat')->numeric()->label('GPS latitude'),
                TextInput::make('gps_lng')->numeric()->label('GPS longitude'),
                Placeholder::make('map_demo')
                    ->label('Map')
                    ->content(function (Get $get) {
                        $lat = $get('gps_lat');
                        $lng = $get('gps_lng');
                        if (! $lat || ! $lng) {
                            return new HtmlString('<span class="text-sm text-gray-500">Enter coordinates for a Demo preview (no external map provider configured).</span>');
                        }

                        return new HtmlString(
                            '<div class="rounded border border-dashed p-3 text-sm bg-gray-50 dark:bg-gray-800">'
                            .'<strong>Demo preview</strong> — lat '.$lat.', lng '.$lng
                            .'<br><span class="text-gray-500">No live map/API call. Configure a map provider in production Settings.</span>'
                            .'</div>'
                        );
                    })
                    ->columnSpanFull(),
            ]),
            Section::make('Survey')->columns(2)->schema([
                Select::make('property_type')->options(fn (Get $get) => Lookup::options('property_type', $get('property_type'))),
                TextInput::make('roof_type_material')->label('Roof type / material'),
                TextInput::make('usable_area_sqm')->numeric()->label('Usable area (m²)'),
                TextInput::make('orientation_tilt')->label('Orientation / tilt'),
                Textarea::make('shading_obstructions')->columnSpanFull(),
                Textarea::make('structural_concerns')->columnSpanFull(),
                Textarea::make('site_access_notes')->label('Site access')->columnSpanFull(),
                Textarea::make('safety_notes')->columnSpanFull(),
            ]),
            Section::make('Utility details')->columns(2)->schema([
                Textarea::make('bill_consumption_evidence')->label('Bill / consumption evidence notes')->columnSpanFull(),
                TextInput::make('monthly_units')->numeric(),
                TextInput::make('sanctioned_load_kw')->numeric()->label('Sanctioned load (kW)'),
                Select::make('phase_type')->options(fn (Get $get) => Lookup::options('phase_type', $get('phase_type'))),
                TextInput::make('utility_discom')->label('Utility / DISCOM'),
                TextInput::make('meter_connection_type')->label('Meter / connection type'),
                TextInput::make('proposed_hybrid_system'),
                TextInput::make('tentative_capacity_kw')->numeric()->label('Tentative capacity (kW)'),
            ]),
            Section::make('Visit outcome')->columns(2)->schema([
                Textarea::make('measurements')->columnSpanFull(),
                Select::make('feasibility')->options(fn (Get $get) => Lookup::options('feasibility', $get('feasibility'))),
                Textarea::make('customer_needs')->columnSpanFull(),
                Textarea::make('objections')->columnSpanFull(),
                Textarea::make('feedback')->columnSpanFull(),
                TextInput::make('recommended_next_step')->columnSpanFull(),
                Textarea::make('preliminary_bom')
                    ->label('Preliminary bill of materials')
                    ->helperText('Free-text for now; link to catalogue products when catalogue module exists.')
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
