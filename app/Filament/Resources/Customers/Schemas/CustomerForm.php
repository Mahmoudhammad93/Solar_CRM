<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Branch;
use App\Models\Lookup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer')->columns(2)->schema([
                TextInput::make('customer_number')->disabled()->dehydrated(false)->visibleOn('edit'),
                Select::make('customer_type')->options(fn (Get $get) => Lookup::options('customer_type', $get('customer_type'))),
                TextInput::make('name')->required(),
                TextInput::make('company_name'),
                TextInput::make('contact_person'),
                TextInput::make('mobile')->tel(),
                TextInput::make('alternate_phone')->tel(),
                TextInput::make('email')->email(),
                Textarea::make('address')->columnSpanFull(),
                TextInput::make('city'),
                TextInput::make('district'),
                TextInput::make('state'),
                TextInput::make('postal_code'),
                TextInput::make('gps_lat')->numeric(),
                TextInput::make('gps_lng')->numeric(),
                Select::make('branch_id')->options(fn () => Branch::query()->pluck('name', 'id')),
                Textarea::make('notes')->columnSpanFull(),
                Select::make('whatsapp_consent_status')
                    ->label('WhatsApp consent')
                    ->options([
                        'unknown' => 'Unknown (send blocked)',
                        'opted_in' => 'Opted in',
                        'opted_out' => 'Opted out (send blocked)',
                    ])
                    ->default('unknown'),
                TextInput::make('whatsapp_consent_source')->label('Consent source'),
                TextInput::make('whatsapp_consent_at')->label('Consent at')->disabled()->dehydrated(false)->visibleOn('edit'),
            ]),
        ]);
    }
}
