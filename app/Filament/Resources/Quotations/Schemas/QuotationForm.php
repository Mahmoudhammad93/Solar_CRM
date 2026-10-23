<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\CalculationRun;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Lookup;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Header')->columns(3)->schema([
                TextInput::make('number')->disabled()->dehydrated(false)->visibleOn('edit'),
                Select::make('proposal_type')->options([
                    'hybrid' => 'Hybrid solar',
                    'pump' => 'Solar water pump',
                ])->required()->live(),
                Select::make('owner_id')->label('Owner')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable()->required(),
                Select::make('lead_id')->options(fn () => Lead::query()->orderByDesc('id')->limit(100)->get()->mapWithKeys(fn ($l) => [$l->id => $l->reference.' — '.$l->name]))->searchable(),
                Select::make('customer_id')->options(fn () => Customer::query()->orderByDesc('id')->limit(100)->get()->mapWithKeys(fn ($c) => [$c->id => $c->customer_number.' — '.$c->name]))->searchable()->live(),
                Select::make('visit_id')->options(fn () => Visit::query()->orderByDesc('id')->limit(100)->pluck('reference', 'id'))->searchable(),
                Select::make('calculation_run_id')
                    ->label('Import CalculationRun')
                    ->options(fn () => CalculationRun::query()->where('calculator_type', 'hybrid')->orderByDesc('id')->limit(50)->pluck('reference', 'id'))
                    ->searchable()
                    ->visible(fn (Get $get) => $get('proposal_type') === 'hybrid'),
                TextInput::make('customer_state')->helperText('Used for CGST/SGST vs IGST (Sample/Demo rule)'),
                TextInput::make('site_address')->columnSpanFull(),
                Select::make('system_type')->options(fn (Get $get) => Lookup::options('system_type', $get('system_type'))),
                TextInput::make('system_capacity_kw')->numeric(),
                TextInput::make('advance_amount')->numeric()->default(0),
                Textarea::make('internal_notes')->columnSpanFull(),
                Textarea::make('customer_terms')->columnSpanFull(),
            ]),
            Section::make('Pump details')
                ->visible(fn (Get $get) => $get('proposal_type') === 'pump')
                ->columns(3)
                ->schema([
                    TextInput::make('pump_details.model')->label('Pump model'),
                    TextInput::make('pump_details.capacity')->label('Capacity'),
                    TextInput::make('pump_details.head_m')->label('Head (m)')->numeric(),
                    TextInput::make('pump_details.discharge')->label('Discharge'),
                    TextInput::make('pump_details.daily_water_requirement')->label('Daily water requirement'),
                    TextInput::make('pump_details.controller')->label('Controller'),
                    TextInput::make('pump_details.subsidy_note')->label('Subsidy (Sample/Demo)')->columnSpanFull()
                        ->default('Sample/Demo subsidy assumption — configure in Settings before claiming real incentives.'),
                ]),
            Section::make('Lines')->schema([
                Repeater::make('lines')->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn () => Product::query()->active()->orderBy('title')->limit(200)->get()->mapWithKeys(fn ($p) => [$p->id => $p->sku.' — '.$p->title]))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $p = Product::query()->find($state);
                            if (! $p) {
                                return;
                            }
                            $set('sku', $p->sku);
                            $set('title', $p->title);
                            $set('description', $p->description);
                            $set('unit', $p->unit);
                            $set('unit_price', (float) $p->sale_price);
                            $set('stock_available', $p->availableStock());
                            $set('stock_indicator', 'check_on_save');
                            $set('line_type', $p->category);
                        }),
                    TextInput::make('sku')->required(),
                    TextInput::make('title')->required()->columnSpan(2),
                    Textarea::make('description')->columnSpanFull(),
                    TextInput::make('quantity')->numeric()->required()->minValue(0)->default(1),
                    TextInput::make('unit')->default('pcs'),
                    TextInput::make('unit_price')->numeric()->required()->minValue(0),
                    TextInput::make('discount_percent')->numeric()->minValue(0)->default(0)->helperText('Limited by Settings max discount'),
                    TextInput::make('gst_rate')->numeric()->default(0.18)->helperText('Fraction e.g. 0.18'),
                    TextInput::make('line_type')->default('product'),
                ])->columns(4)->defaultItems(1)->minItems(1)->collapsible(),
            ]),
        ]);
    }
}
