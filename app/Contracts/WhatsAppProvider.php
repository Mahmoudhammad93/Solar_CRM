<?php

namespace App\Contracts;

/**
 * Outbound WhatsApp channel. Demo is the default; configured providers stay behind this interface.
 */
interface WhatsAppProvider
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * @param  array<string, mixed>  $message
     * @return array{status: string, response?: mixed, error?: string, simulated?: bool}
     */
    public function send(array $message): array;

    /**
     * @return array{ok: bool, mode: string, message: string, simulated: bool}
     */
    public function testConnection(): array;
}
