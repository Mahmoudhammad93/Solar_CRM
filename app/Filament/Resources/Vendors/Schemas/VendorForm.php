<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('contact_name'),
            TextInput::make('email')->email(),
            TextInput::make('phone')->tel(),
            Textarea::make('address')->rows(2)->columnSpanFull(),
            TextInput::make('tax_identifier')->label('Tax identifier'),
            Select::make('status')->options([
                'active' => 'Active',
                'inactive' => 'Inactive',
            ])->required()->default('active'),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }
}
