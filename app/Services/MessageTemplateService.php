<?php

namespace App\Services;

use App\Support\MessageTemplateCatalog;
use Illuminate\Validation\ValidationException;

class MessageTemplateService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return list<string>
     */
    public function extractPlaceholders(string $body): array
    {
        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', $body, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1] ?? [])));
    }

    /**
     * @throws ValidationException
     */
    public function assertPlaceholdersAllowed(string $body): void
    {
        $found = $this->extractPlaceholders($body);
        $allowed = MessageTemplateCatalog::PLACEHOLDERS;
        $unknown = array_values(array_diff($found, $allowed));
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'template' => 'Unknown placeholders: '.implode(', ', array_map(fn ($p) => '{{'.$p.'}}', $unknown))
                    .'. Allowed: '.implode(', ', array_map(fn ($p) => '{{'.$p.'}}', $allowed)),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function render(string $body, array $data): string
    {
        $this->assertPlaceholdersAllowed($body);
        $needed = $this->extractPlaceholders($body);
        $missing = [];
        foreach ($needed as $key) {
            if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                $missing[] = '{{'.$key.'}}';
            }
        }
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'template' => 'Missing template variables: '.implode(', ', $missing),
            ]);
        }

        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $m) use ($data) {
            $key = strtolower($m[1]);

            return (string) ($data[$key] ?? '');
        }, $body) ?? $body;
    }

    public function bodyFor(string $templateKey): string
    {
        $defs = MessageTemplateCatalog::definitions();
        if (! isset($defs[$templateKey])) {
            throw ValidationException::withMessages(['template' => "Unknown template key: {$templateKey}"]);
        }

        $stored = $this->settings->get('templates.whatsapp.'.$templateKey);
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return $defs[$templateKey]['default_body'];
    }

    public function emailBodyFor(string $templateKey): string
    {
        $defs = MessageTemplateCatalog::definitions();
        if (! isset($defs[$templateKey])) {
            throw ValidationException::withMessages(['template' => "Unknown template key: {$templateKey}"]);
        }

        $stored = $this->settings->get('templates.email.'.$templateKey);
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return $defs[$templateKey]['default_body'];
    }

    /**
     * @return array{body: string, preview: string}
     */
    public function preview(string $templateKey, string $channel = 'whatsapp'): array
    {
        $body = $channel === 'email' ? $this->emailBodyFor($templateKey) : $this->bodyFor($templateKey);
        $this->assertPlaceholdersAllowed($body);
        $preview = $this->render($body, MessageTemplateCatalog::sampleData());

        return ['body' => $body, 'preview' => $preview];
    }
}
