<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\NumberingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference'] = app(NumberingService::class)->next('product');
        $data['created_by'] = Filament::auth()->id();
        $data['updated_by'] = Filament::auth()->id();
        $data['is_archived'] = ($data['status'] ?? '') === 'archived';

        if (is_array($data['image_path'] ?? null)) {
            $data['image_path'] = $data['image_path'][0] ?? null;
        }

        return $data;
    }
}
