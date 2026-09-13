<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('ar_teaser_accent')->nullable()->after('ar_teaser_subtitle');
            $table->string('ar_teaser_footer')->nullable()->after('ar_teaser_accent');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['ar_teaser_accent', 'ar_teaser_footer']);
        });
    }
};
