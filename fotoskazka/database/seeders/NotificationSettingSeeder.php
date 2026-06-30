<?php

namespace Database\Seeders;

use App\Models\NotificationSetting;
use Illuminate\Database\Seeder;

class NotificationSettingSeeder extends Seeder
{
    public function run(): void
    {
        NotificationSetting::create([
            'title' => 'Основные настройки',
            'email_enabled' => true,
            'email_recipients' => [],
            'telegram_enabled' => false,
            'telegram_bot_token' => null,
            'telegram_chat_id' => null,
        ]);
    }
}
