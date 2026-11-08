<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->boolean('max_enabled')->default(false)->after('telegram_chat_id');
            $table->string('max_bot_token', 500)->nullable()->after('max_enabled');
            $table->string('max_chat_id', 100)->nullable()->after('max_bot_token');
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn(['max_enabled', 'max_bot_token', 'max_chat_id']);
        });
    }
};
