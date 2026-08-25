<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_items', function (Blueprint $table) {
            $table->foreignId('icon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subtitle')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('service_items', function (Blueprint $table) {
            $table->dropForeign(['icon_id']);
            $table->dropColumn(['icon_id', 'subtitle']);
        });
    }
};
