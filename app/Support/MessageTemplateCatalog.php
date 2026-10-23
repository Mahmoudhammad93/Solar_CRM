<?php

namespace App\Support;

/**
 * Approved WhatsApp/email template keys and safe placeholder whitelist.
 */
final class MessageTemplateCatalog
{
    /** @var list<string> */
    public const PLACEHOLDERS = [
        'customer_name',
        'contact_name',
        'quote_number',
        'lead_reference',
        'visit_date',
        'visit_time',
        'project_number',
        'milestone_name',
        'invoice_number',
        'amount',
        'ticket_number',
        'ticket_status',
        'amc_number',
        'renewal_date',
        'company_name',
        'link',
        'owner_name',
    ];

    /**
     * @return array<string, array{label: string, channel: list<string>, default_body: string}>
     */
    public static function definitions(): array
    {
        return [
            'lead_assignment' => [
                'label' => 'Lead assignment',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Hi {{customer_name}}, {{owner_name}} from {{company_name}} has been assigned to your enquiry {{lead_reference}}.',
            ],
            'follow_up_reminder' => [
                'label' => 'Follow-up reminder',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Reminder: follow up with {{customer_name}} ({{lead_reference}}) today.',
            ],
            'site_visit_confirmation' => [
                'label' => 'Site visit confirmation',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Hi {{customer_name}}, your site visit is confirmed for {{visit_date}} {{visit_time}}. — {{company_name}}',
            ],
            'quotation_ready' => [
                'label' => 'Quotation ready / shared',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Hi {{customer_name}}, quotation {{quote_number}} is ready. View: {{link}}',
            ],
            'quote_accepted' => [
                'label' => 'Quote accepted',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Thank you {{customer_name}}. Quotation {{quote_number}} was accepted. Our team will proceed next steps.',
            ],
            'project_milestone_update' => [
                'label' => 'Project milestone update',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Update for project {{project_number}}: milestone {{milestone_name}} was updated.',
            ],
            'invoice_payment_update' => [
                'label' => 'Invoice / payment update',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Invoice {{invoice_number}} update for {{customer_name}}. Amount: {{amount}}. Details: {{link}}',
            ],
            'ticket_status' => [
                'label' => 'Ticket status',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Ticket {{ticket_number}} is now {{ticket_status}}. — {{company_name}}',
            ],
            'amc_renewal' => [
                'label' => 'AMC renewal',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'AMC {{amc_number}} for {{customer_name}} renews on {{renewal_date}}. Contact us to continue coverage.',
            ],
            'calculator_share' => [
                'label' => 'Calculator / report share',
                'channel' => ['whatsapp', 'email'],
                'default_body' => 'Hi {{customer_name}}, your solar calculator report is available: {{link}}',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sampleData(): array
    {
        return [
            'customer_name' => 'Asha Patil',
            'contact_name' => 'Asha Patil',
            'quote_number' => 'QUO-2026-0001',
            'lead_reference' => 'LEAD-2026-0001',
            'visit_date' => '2026-10-25',
            'visit_time' => '10:30',
            'project_number' => 'PRJ-2026-0001',
            'milestone_name' => 'Installation',
            'invoice_number' => 'INV-2026-0001',
            'amount' => '5000.00',
            'ticket_number' => 'TKT-2026-0001',
            'ticket_status' => 'In Progress',
            'amc_number' => 'AMC-2026-0001',
            'renewal_date' => '2027-01-15',
            'company_name' => 'Rootflash Solar',
            'link' => 'https://example.test/share/demo',
            'owner_name' => 'Sales Demo',
        ];
    }
}
