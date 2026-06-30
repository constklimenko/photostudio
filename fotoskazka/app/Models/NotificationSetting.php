<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'title',
        'email_enabled',
        'email_recipients',
        'telegram_enabled',
        'telegram_bot_token',
        'telegram_chat_id',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'telegram_enabled' => 'boolean',
            'email_recipients' => 'array',
        ];
    }
}
