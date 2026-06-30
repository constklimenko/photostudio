<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramNotifier
{
    protected ?string $token;

    protected ?string $chatId;

    public function __construct(?string $botToken = null, ?string $chatId = null)
    {
        $this->token = $botToken ?? config('services.telegram.bot_token');
        $this->chatId = $chatId ?? config('services.telegram.chat_id');
    }

    public function isConfigured(): bool
    {
        return filled($this->token) && filled($this->chatId);
    }

    public function sendMessage(string $message): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Telegram not configured: bot token or chat id is missing');
        }

        $response = Http::timeout(5)->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Telegram API error: status {$response->status()}, body: {$response->body()}"
            );
        }
    }
}
