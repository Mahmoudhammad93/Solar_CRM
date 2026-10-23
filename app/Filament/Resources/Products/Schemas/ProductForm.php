<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Lookup;
use App\Models\ProductAttribute;
use App\Models\Vendor;
use App\Services\SettingsService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $settings = app(SettingsService::class);

        return $schema->components([
            Section::make('Identity')->columns(2)->schema([
                TextInput::make('reference')->disabled()->dehydrated(false)->visibleOn('edit'),
                TextInput::make('sku')->required()->maxLength(64)->unique(ignoreRecord: true),
                Select::make('category')
                    ->options(fn (Get $get) => Lookup::options('product_category', $get('category')))
                    ->required()
                    ->live()
                    ->searchable(),
                TextInput::make('brand')->datalist(fn () => array_values(Lookup::options('product_brand')))->required(),
                TextInput::make('model'),
                TextInput::make('title')->required()->columnSpanFull(),
                Textarea::make('description')->columnSpanFull()->rows(3),
            ]),
            Section::make('Technical specs')
                ->description('Attribute keys come from Settings F / product_attributes for the selected category.')
                ->schema([
                    KeyValue::make('specs')
                        ->keyLabel('Attribute')
                        ->valueLabel('Value')
                        ->helperText(function (Get $get) {
                            $cat = (string) $get('category');
                            if ($cat === '') {
                                return 'Select a category to see recommended attributes.';
                            }
                            $attrs = ProductAttribute::query()->forCategory($cat)->get()
                                ->map(fn ($a) => $a->attribute_key.($a->unit ? " ({$a->unit})" : ''))
                                ->implode(', ');

                            return $attrs !== '' ? "Suggested: {$attrs}" : 'No attributes configured for this category.';
                        }),
                ]),
            Section::make('Commercial')->columns(3)->schema([
                Select::make('unit')->options(fn (Get $get) => Lookup::options('product_unit', $get('unit')))->required(),
                TextInput::make('purchase_cost')->numeric()->required()->default(0),
                TextInput::make('sale_price')->numeric()->required()->default(0),
                TextInput::make('gst_rate')->numeric()->label('GST %')
                    ->default(fn () => $settings->get('inventory.default_gst_rate', 18)),
                TextInput::make('warranty_months')->numeric()->integer()
                    ->default(fn () => $settings->get('inventory.default_warranty_months', 12)),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->options(fn () => Vendor::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'archived' => 'Archived',
                    ])
                    ->required()
                    ->default('active'),
                Toggle::make('tracks_serial_batch')
                    ->label('Serial / batch tracking')
                    ->default(fn () => (bool) $settings->get('inventory.serial_batch_tracking_default', false)),
                TextInput::make('reorder_threshold')->numeric()
                    ->default(fn () => $settings->get('inventory.default_reorder_threshold', 5)),
                FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->visibility('public')
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
