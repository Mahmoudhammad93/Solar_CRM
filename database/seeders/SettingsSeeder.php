<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingsService::class);

        $defaults = [
            ['key' => 'branding.app_name', 'value' => 'Solar CRM', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.company_name', 'value' => 'Rootflash Demo Solar Pvt Ltd', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.legal_name', 'value' => 'Rootflash Demo Solar Private Limited', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.address', 'value' => '12 Demo Industrial Estate, Cairo Demo Zone', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.phone', 'value' => '+20 100 000 0000', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.support_email', 'value' => 'support@solar-crm.test', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.website', 'value' => 'https://solar-crm.test', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.gstin', 'value' => 'DEMOGSTIN0001', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.invoice_address', 'value' => '12 Demo Industrial Estate, Cairo Demo Zone', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.footer_text', 'value' => 'Solar CRM — Demo instance for evaluation only', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.accent_color', 'value' => '#F59E0B', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'branding.logo_path', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'branding.favicon_path', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'branding.login_logo_path', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'security.session_idle_minutes', 'value' => 120, 'group' => 'security', 'type' => 'integer', 'is_sample' => false],
            ['key' => 'security.password_min_length', 'value' => 8, 'group' => 'security', 'type' => 'integer', 'is_sample' => false],
            ['key' => 'documents.max_upload_kb', 'value' => 5120, 'group' => 'documents', 'type' => 'integer', 'is_sample' => false],
            ['key' => 'integrations.map_provider_mode', 'value' => 'demo', 'group' => 'integrations', 'type' => 'string', 'is_sample' => true],
            ['key' => 'numbering.lead.prefix', 'value' => 'LEAD', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.lead.format', 'value' => '{PREFIX}-{YYYY}-{####}', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.lead.reset_yearly', 'value' => false, 'group' => 'numbering', 'type' => 'boolean', 'is_sample' => false],
            ['key' => 'numbering.customer.prefix', 'value' => 'CUS', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.customer.format', 'value' => '{PREFIX}-{YYYY}-{####}', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.task.prefix', 'value' => 'TSK', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.task.format', 'value' => '{PREFIX}-{YYYY}-{####}', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.quote.prefix', 'value' => 'QUO', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.quote.format', 'value' => '{PREFIX}-{YYYY}-{####}', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.quote.reset_yearly', 'value' => false, 'group' => 'numbering', 'type' => 'boolean', 'is_sample' => false],
            ['key' => 'numbering.visit.prefix', 'value' => 'VIS', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.order.prefix', 'value' => 'ORD', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.project.prefix', 'value' => 'PRJ', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.invoice.prefix', 'value' => 'INV', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.ticket.prefix', 'value' => 'TKT', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.amc.prefix', 'value' => 'AMC', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'numbering.product.prefix', 'value' => 'PRD', 'group' => 'numbering', 'type' => 'string', 'is_sample' => false],
            ['key' => 'inventory.default_reorder_threshold', 'value' => 5, 'group' => 'inventory', 'type' => 'integer', 'is_sample' => false],
            ['key' => 'inventory.serial_batch_tracking_default', 'value' => false, 'group' => 'inventory', 'type' => 'boolean', 'is_sample' => false],
            ['key' => 'inventory.reservation_behavior', 'value' => 'hard', 'group' => 'inventory', 'type' => 'string', 'is_sample' => false],
            ['key' => 'inventory.allow_negative_stock', 'value' => false, 'group' => 'inventory', 'type' => 'boolean', 'is_sample' => false],
            ['key' => 'inventory.default_gst_rate', 'value' => 18, 'group' => 'inventory', 'type' => 'integer', 'is_sample' => false],
            ['key' => 'inventory.default_warranty_months', 'value' => 12, 'group' => 'inventory', 'type' => 'integer', 'is_sample' => false],
        ];

        foreach ($defaults as $row) {
            $settings->set($row['key'], $row['value'], [
                'group' => $row['group'],
                'type' => $row['type'],
                'is_sample' => $row['is_sample'] ?? true,
                'status' => 'active',
                'effective_from' => now()->toDateString(),
                'source_reference' => 'seed',
                'change_summary' => 'Seeded default',
            ], null);
        }
    }
}
