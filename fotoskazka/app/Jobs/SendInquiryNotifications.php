<?php

namespace App\Jobs;

use App\Mail\NewInquiryMail;
use App\Models\Inquiry;
use App\Models\NotificationSetting;
use App\Services\MaxNotifier;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendInquiryNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(public Inquiry $inquiry) {}

    public function handle(): void
    {
        $errors = [];

        $this->sendEmail($errors);
        $this->sendTelegram($errors);
        $this->sendMax($errors);

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

        try {
            $notifier->sendMessage($this->inquiryMessage());
        } catch (\Throwable $e) {
            $errors[] = "Telegram: {$e->getMessage()}";
        }
    }

    protected function sendMax(array &$errors): void
    {
        $settings = NotificationSetting::first();

        if (! $settings || ! $settings->max_enabled) {
            return;
        }

        $notifier = new MaxNotifier(
            botToken: $settings->max_bot_token,
            chatId: $settings->max_chat_id,
        );

        if (! $notifier->isConfigured()) {
            return;
        }

        try {
            $notifier->sendMessage($this->inquiryMessage());
        } catch (\Throwable $e) {
            $errors[] = "Max: {$e->getMessage()}";
        }
    }

    protected function inquiryMessage(): string
    {
        $service = e($this->inquiry->service?->title ?? '—');
        $name = e($this->inquiry->name);
        $phone = e($this->inquiry->phone);
        $email = e($this->inquiry->email);
        $date = e($this->inquiry->shooting_date);
        $message = e(Str::limit((string) $this->inquiry->message, 3000));

        return "📩 <b>Новая заявка</b>\n\n"
            ."Имя: {$name}\n"
            ."Телефон: {$phone}\n"
            ."Email: {$email}\n"
            ."Услуга: {$service}\n"
            ."Дата: {$date}\n"
            ."Комментарий: {$message}\n\n"
            .'Открыть: '.e(url("/admin/inquiries/{$this->inquiry->id}/edit"));
    }
}
