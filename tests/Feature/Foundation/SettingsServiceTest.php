<?php

namespace Tests\Feature\Foundation;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_invalidates_on_save(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('branding.app_name', 'Alpha CRM', [
            'group' => 'branding',
            'type' => 'string',
            'is_sample' => false,
        ]);

        $this->assertSame('Alpha CRM', $settings->get('branding.app_name'));

        Cache::put('app.settings.map', [
            'branding.app_name' => [
                'value' => 'Stale',
                'type' => 'string',
                'group' => 'branding',
                'unit' => null,
                'status' => 'active',
                'is_sample' => false,
                'source_reference' => null,
                'effective_from' => null,
            ],
        ], 60);

        $this->assertSame('Stale', $settings->get('branding.app_name'));

        $settings->set('branding.app_name', 'Beta CRM', [
            'group' => 'branding',
            'type' => 'string',
            'is_sample' => false,
        ]);

        $this->assertSame('Beta CRM', $settings->get('branding.app_name'));
    }
}
