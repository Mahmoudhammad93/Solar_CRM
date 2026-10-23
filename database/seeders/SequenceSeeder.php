<?php

namespace Database\Seeders;

use App\Services\NumberingService;
use Illuminate\Database\Seeder;

class SequenceSeeder extends Seeder
{
    public function run(): void
    {
        $numbering = app(NumberingService::class);

        foreach (['lead', 'customer', 'task', 'visit', 'quote', 'order', 'project', 'invoice', 'ticket', 'amc', 'product', 'calculation'] as $entity) {
            $numbering->syncFromSettings($entity);
        }
    }
}
