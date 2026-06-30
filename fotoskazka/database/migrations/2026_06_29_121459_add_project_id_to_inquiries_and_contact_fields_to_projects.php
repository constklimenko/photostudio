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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('shooting_date');
            $table->string('contact_phone', 50)->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_phone');

            $table->index('contact_phone');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('shooting_date')
                ->constrained()
                ->nullOnDelete();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['contact_phone']);
            $table->dropColumn(['contact_name', 'contact_phone', 'contact_email']);
        });
    }
};
