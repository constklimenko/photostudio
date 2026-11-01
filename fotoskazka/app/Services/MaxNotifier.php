<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MaxNotifier
{
    protected ?string $token;

    protected ?string $chatId;

    public function __construct(?string $botToken = null, ?string $chatId = null)
    {
        $this->token = $botToken ?? config('services.max.bot_token');
        $this->chatId = $chatId ?? config('services.max.chat_id');
    }

    public function isConfigured(): bool
    {
        return filled($this->token) && filled($this->chatId);
    }

    public function sendMessage(string $message): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Max not configured: bot token or chat id is missing');
        }

        $response = Http::timeout(5)
            ->withHeaders(['Authorization' => $this->token])
            ->post("https://platform-api2.max.ru/messages?chat_id={$this->chatId}", [
                'text' => $message,
                'format' => 'html',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Max API error: status {$response->status()}, body: {$response->body()}"
            );
        }
    }
}
