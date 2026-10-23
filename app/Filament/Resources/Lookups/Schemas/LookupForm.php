<?php

namespace App\Filament\Resources\Lookups\Schemas;

use App\Models\Lookup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LookupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->options(Lookup::types()->all())
                ->required()
                ->searchable(),
            TextInput::make('value')
                ->helperText('Stable key stored on records (do not change lightly).')
                ->required()
                ->maxLength(100),
            TextInput::make('label')->required()->maxLength(255),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true)
                ->helperText('Deactivating hides the value from new forms but keeps it on existing records.'),
        ]);
    }
}
