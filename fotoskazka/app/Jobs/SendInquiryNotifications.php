<?php

namespace App\Jobs;

use App\Mail\NewInquiryMail;
use App\Models\Inquiry;
use App\Models\NotificationSetting;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInquiryNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(public Inquiry $inquiry) {}

    public function handle(): void
    {
        $errors = [];

        $this->sendEmail($errors);
        $this->sendTelegram($errors);

        $this->inquiry->update([
            'notification_error' => $errors ? implode("\n", $errors) : null,
        ]);
    }

    protected function sendEmail(array &$errors): void
    {
        $settings = NotificationSetting::first();

        if (! $settings || ! $settings->email_enabled) {
            return;
        }

        $recipients = $settings->email_recipients ?? [];

        foreach ($recipients as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                Mail::to($email)->send(new NewInquiryMail($this->inquiry));
            } catch (\Throwable $e) {
                $errors[] = "Email to {$email}: {$e->getMessage()}";
            }
        }
    }

    protected function sendTelegram(array &$errors): void
    {
        $settings = NotificationSetting::first();

        if (! $settings || ! $settings->telegram_enabled) {
            return;
        }

        $notifier = new TelegramNotifier(
            botToken: $settings->telegram_bot_token,
            chatId: $settings->telegram_chat_id,
        );

        if (! $notifier->isConfigured()) {
            return;
        }

        $service = $this->inquiry->service?->title ?? '—';

        $text = "📩 <b>Новая заявка</b>\n\n"
            ."Имя: {$this->inquiry->name}\n"
            ."Телефон: {$this->inquiry->phone}\n"
            ."Email: {$this->inquiry->email}\n"
            ."Услуга: {$service}\n"
            ."Дата: {$this->inquiry->shooting_date}\n"
            ."Комментарий: {$this->inquiry->message}\n\n"
            .'Открыть: '.url("/admin/inquiries/{$this->inquiry->id}/edit");

        try {
            $notifier->sendMessage($text);
        } catch (\Throwable $e) {
            $errors[] = "Telegram: {$e->getMessage()}";
        }
    }
}
