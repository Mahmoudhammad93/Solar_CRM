<?php

namespace App\Filament\Pages;

use App\Services\MessageService;
use App\Services\MessageTemplateService;
use App\Services\SettingsService;
use App\Support\MessageTemplateCatalog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class CommunicationsSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 95;

    protected static ?string $navigationLabel = 'Communications';

    protected static ?string $title = 'Communications (WhatsApp / Email / Reminders)';

    protected string $view = 'filament.pages.communications-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $previewText = null;

    public static function canAccess(): bool
    {
        return Gate::allows('settings.view');
    }

    public function mount(): void
    {
        abort_unless(Gate::allows('settings.view'), 403);
        $s = app(SettingsService::class);
        $fill = [
            'whatsapp_mode' => $s->get('whatsapp.mode', 'demo'),
            'whatsapp_provider_name' => $s->get('whatsapp.provider_name', 'Demo'),
            'whatsapp_business_phone' => $s->get('whatsapp.business_phone'),
            'whatsapp_display_account_id' => $s->get('whatsapp.display_account_id'),
            'whatsapp_approved_templates' => $s->get('whatsapp.approved_template_names', 'lead_assignment,follow_up_reminder,site_visit_confirmation,quotation_ready,quote_accepted,project_milestone_update,invoice_payment_update,ticket_status,amc_renewal'),
            'default_calling_code' => $s->get('branding.default_calling_code', '+91'),
            'email_mode' => $s->get('email.mode', 'log'),
            'email_sender_name' => $s->get('email.sender_name', $s->get('branding.company_name')),
            'email_sender_address' => $s->get('email.sender_address', $s->get('branding.support_email')),
            'reminder_lead_hours' => $s->get('reminders.lead_follow_up_hours', 24),
            'reminder_task_hours' => $s->get('reminders.task_due_hours_before', 4),
            'reminder_channel_in_app' => (bool) $s->get('reminders.channel_in_app', true),
            'reminder_channel_email' => (bool) $s->get('reminders.channel_email_demo', false),
            'working_hours_start' => $s->get('reminders.working_hours_start', '09:00'),
            'working_hours_end' => $s->get('reminders.working_hours_end', '18:00'),
            'preview_template' => 'quotation_ready',
        ];
        foreach (array_keys(MessageTemplateCatalog::definitions()) as $key) {
            $fill['wa_'.$key] = $s->get('templates.whatsapp.'.$key) ?: MessageTemplateCatalog::definitions()[$key]['default_body'];
            $fill['em_'.$key] = $s->get('templates.email.'.$key) ?: MessageTemplateCatalog::definitions()[$key]['default_body'];
        }
        $this->form->fill($fill);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $canEdit = Gate::allows('settings.update');
        $messages = app(MessageService::class);
        $creds = $messages->credentialsStatus();
        $smtpConfigured = filled(config('mail.mailers.smtp.username')) || filled(env('MAIL_PASSWORD'));

        $waTemplateFields = [];
        $emTemplateFields = [];
        foreach (MessageTemplateCatalog::definitions() as $key => $def) {
            $waTemplateFields[] = Textarea::make('wa_'.$key)->label('WA: '.$def['label'])->rows(3)->disabled(! $canEdit);
            $emTemplateFields[] = Textarea::make('em_'.$key)->label('Email: '.$def['label'])->rows(3)->disabled(! $canEdit);
        }

        return $schema->components([
            Tabs::make('Communications')->tabs([
                Tab::make('WhatsApp')->schema([
                    Section::make('Mode & connection status')->schema([
                        Select::make('whatsapp_mode')->options([
                            'demo' => 'Demo (default — SIMULATED, not delivered)',
                            'configured' => 'Configured provider (credentials from env only)',
                        ])->disabled(! $canEdit)->required(),
                        TextInput::make('whatsapp_provider_name')->label('Provider name (display)')->disabled(! $canEdit),
                        TextInput::make('whatsapp_business_phone')->label('Business phone / sender (non-secret)')->disabled(! $canEdit),
                        TextInput::make('whatsapp_display_account_id')->label('Business / account ID (non-secret display)')->disabled(! $canEdit),
                        TextInput::make('whatsapp_approved_templates')->label('Approved template names (comma-separated)')->disabled(! $canEdit)->columnSpanFull(),
                        TextInput::make('default_calling_code')->label('Default calling code')->disabled(! $canEdit),
                        Placeholder::make('cred_status')
                            ->label('Credential status')
                            ->content($creds['status'].' — '.$creds['setup_message']),
                        Placeholder::make('webhook_status')
                            ->label('Webhook / callback')
                            ->content('Not implemented — no inbound WhatsApp webhook endpoint in this build.'),
                        Placeholder::make('secret_notice')
                            ->label('Secrets')
                            ->content('API tokens are NEVER requested or shown here. Configure WHATSAPP_TOKEN / WHATSAPP_PHONE_NUMBER_ID on the server only.'),
                    ])->columns(2),
                    Section::make('WhatsApp templates')->schema($waTemplateFields)->columns(1),
                    Section::make('Preview')->schema([
                        Select::make('preview_template')->options(
                            collect(MessageTemplateCatalog::definitions())->mapWithKeys(fn ($d, $k) => [$k => $d['label']])->all()
                        )->live(),
                        Placeholder::make('preview_out')
                            ->label('Preview (sample data)')
                            ->content(fn (): string => $this->previewText ?? 'Use “Preview template” action.'),
                    ]),
                ]),
                Tab::make('Email')->schema([
                    Section::make('Email mode')->schema([
                        Select::make('email_mode')->options([
                            'log' => 'Log / demo (default — SIMULATED)',
                            'smtp' => 'SMTP (secrets from MAIL_* env only)',
                        ])->disabled(! $canEdit),
                        TextInput::make('email_sender_name')->label('Sender display name')->disabled(! $canEdit),
                        TextInput::make('email_sender_address')->email()->label('Sender address')->disabled(! $canEdit),
                        Placeholder::make('smtp_status')
                            ->label('SMTP credentials')
                            ->content($smtpConfigured
                                ? 'Configured (from environment). Test email action: Unavailable — no live SMTP test in this build.'
                                : 'Not configured — set MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD in .env. Secrets never appear in this form.'),
                        Placeholder::make('test_email')
                            ->label('Test email')
                            ->content('Unavailable — mark as Not implemented for live send; demo share uses MessageService (SIMULATED).'),
                    ])->columns(2),
                    Section::make('Email templates')->schema($emTemplateFields),
                ]),
                Tab::make('Reminders')->schema([
                    Section::make('Reminder & notification settings')->schema([
                        TextInput::make('reminder_lead_hours')->numeric()->label('Lead follow-up reminder (hours before/at due)')->disabled(! $canEdit),
                        TextInput::make('reminder_task_hours')->numeric()->label('Task due reminder (hours before)')->disabled(! $canEdit),
                        Toggle::make('reminder_channel_in_app')->label('In-app notifications')->disabled(! $canEdit),
                        Toggle::make('reminder_channel_email')->label('Email channel (demo/log only)')->disabled(! $canEdit),
                        TimePicker::make('working_hours_start')->seconds(false)->disabled(! $canEdit),
                        TimePicker::make('working_hours_end')->seconds(false)->disabled(! $canEdit),
                        Placeholder::make('sla_hint')
                            ->label('Ticket / project SLA')
                            ->content('Ticket and project SLA hours remain under Settings → Service / Projects. reminders:dispatch creates in-app UserNotifications from stored leads/tasks.'),
                    ])->columns(2),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save')
                            ->submit('save')
                            ->visible(fn (): bool => Gate::allows('settings.update')),
                        Action::make('preview')
                            ->label('Preview template')
                            ->color('gray')
                            ->action(fn () => $this->runPreview()),
                        Action::make('testConnection')
                            ->label('Test WhatsApp connection')
                            ->color('warning')
                            ->visible(fn (): bool => Gate::allows('settings.update'))
                            ->action(fn () => $this->runTestConnection()),
                        Action::make('cancel')
                            ->label('Cancel')
                            ->color('gray')
                            ->action(fn () => $this->mount()),
                    ])->alignment(Alignment::Start),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(Gate::allows('settings.update'), 403);
        $data = $this->form->getState();
        $settings = app(SettingsService::class);
        $userId = Auth::id();
        $templates = app(MessageTemplateService::class);

        foreach (array_keys(MessageTemplateCatalog::definitions()) as $key) {
            $wa = (string) ($data['wa_'.$key] ?? '');
            $em = (string) ($data['em_'.$key] ?? '');
            try {
                $templates->assertPlaceholdersAllowed($wa);
                $templates->assertPlaceholdersAllowed($em);
            } catch (ValidationException $e) {
                Notification::make()->title('Template validation failed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();

                return;
            }
        }

        $map = [
            'whatsapp.mode' => ['value' => $data['whatsapp_mode'] ?? 'demo', 'group' => 'whatsapp', 'type' => 'string'],
            'whatsapp.provider_name' => ['value' => $data['whatsapp_provider_name'] ?? 'Demo', 'group' => 'whatsapp', 'type' => 'string'],
            'whatsapp.business_phone' => ['value' => $data['whatsapp_business_phone'] ?? null, 'group' => 'whatsapp', 'type' => 'string'],
            'whatsapp.display_account_id' => ['value' => $data['whatsapp_display_account_id'] ?? null, 'group' => 'whatsapp', 'type' => 'string'],
            'whatsapp.approved_template_names' => ['value' => $data['whatsapp_approved_templates'] ?? '', 'group' => 'whatsapp', 'type' => 'string'],
            'branding.default_calling_code' => ['value' => $data['default_calling_code'] ?? '+91', 'group' => 'branding', 'type' => 'string'],
            'email.mode' => ['value' => $data['email_mode'] ?? 'log', 'group' => 'email', 'type' => 'string'],
            'email.sender_name' => ['value' => $data['email_sender_name'] ?? null, 'group' => 'email', 'type' => 'string'],
            'email.sender_address' => ['value' => $data['email_sender_address'] ?? null, 'group' => 'email', 'type' => 'string'],
            'reminders.lead_follow_up_hours' => ['value' => $data['reminder_lead_hours'] ?? 24, 'group' => 'reminders', 'type' => 'integer'],
            'reminders.task_due_hours_before' => ['value' => $data['reminder_task_hours'] ?? 4, 'group' => 'reminders', 'type' => 'integer'],
            'reminders.channel_in_app' => ['value' => (bool) ($data['reminder_channel_in_app'] ?? true), 'group' => 'reminders', 'type' => 'boolean'],
            'reminders.channel_email_demo' => ['value' => (bool) ($data['reminder_channel_email'] ?? false), 'group' => 'reminders', 'type' => 'boolean'],
            'reminders.working_hours_start' => ['value' => $data['working_hours_start'] ?? '09:00', 'group' => 'reminders', 'type' => 'string'],
            'reminders.working_hours_end' => ['value' => $data['working_hours_end'] ?? '18:00', 'group' => 'reminders', 'type' => 'string'],
        ];

        foreach (array_keys(MessageTemplateCatalog::definitions()) as $key) {
            $map['templates.whatsapp.'.$key] = ['value' => $data['wa_'.$key] ?? '', 'group' => 'templates', 'type' => 'string'];
            $map['templates.email.'.$key] = ['value' => $data['em_'.$key] ?? '', 'group' => 'templates', 'type' => 'string'];
        }

        foreach ($map as $key => $meta) {
            $settings->set($key, $meta['value'], [
                'group' => $meta['group'],
                'type' => $meta['type'],
                'is_sample' => false,
                'change_summary' => "Updated {$key}",
            ], $userId);
        }

        Notification::make()->title('Communications settings saved')->success()->send();
    }

    public function runPreview(): void
    {
        $key = (string) ($this->data['preview_template'] ?? 'quotation_ready');
        try {
            // Use form body if present
            $body = (string) ($this->data['wa_'.$key] ?? app(MessageTemplateService::class)->bodyFor($key));
            app(MessageTemplateService::class)->assertPlaceholdersAllowed($body);
            $this->previewText = app(MessageTemplateService::class)->render($body, MessageTemplateCatalog::sampleData());
            Notification::make()->title('Preview ready (sample data)')->success()->send();
        } catch (ValidationException $e) {
            $this->previewText = null;
            Notification::make()->title('Preview failed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
        }
    }

    public function runTestConnection(): void
    {
        abort_unless(Gate::allows('settings.update'), 403);
        $result = app(MessageService::class)->testConnection();
        Notification::make()
            ->title($result['ok'] ? 'Connection check' : 'Connection check')
            ->body($result['message'])
            ->{$result['simulated'] ? 'warning' : ($result['ok'] ? 'success' : 'danger')}()
            ->send();
    }
}
