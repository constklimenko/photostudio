<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('email_enabled')->default(true);
            $table->text('email_recipients')->nullable()->comment('Через запятую');
            $table->boolean('telegram_enabled')->default(false);
            $table->string('telegram_bot_token', 500)->nullable();
            $table->string('telegram_chat_id', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
