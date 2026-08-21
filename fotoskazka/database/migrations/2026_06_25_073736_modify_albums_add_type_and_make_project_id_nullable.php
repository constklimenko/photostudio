<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();

            $table->string('type', 20)->default('portfolio')->after('description');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }
};
