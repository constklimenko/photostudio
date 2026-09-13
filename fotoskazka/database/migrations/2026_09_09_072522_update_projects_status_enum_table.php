<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $this->setStatusEnum("ENUM('draft','shooting_completed','reshoot','processing','layout_approval','printing','completed','archived') NOT NULL DEFAULT 'draft'");
        } else {
            $this->rebuildSqliteTable(old: false);
        }

        DB::table('projects')
            ->where('status', 'active')
            ->update(['status' => 'processing']);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(old: true);
        } else {
            $this->setStatusEnum("ENUM('draft','active','completed','archived') NOT NULL DEFAULT 'draft'");
        }

        DB::table('projects')
            ->whereIn('status', [
                'shooting_completed',
                'reshoot',
                'processing',
                'layout_approval',
                'printing',
            ])
            ->update(['status' => 'active']);
    }

    private function setStatusEnum(string $definition): void
    {
        DB::statement("ALTER TABLE projects MODIFY status {$definition}");
    }

    private function rebuildSqliteTable(bool $old): void
    {
        $current = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'projects'");

        if ($current === null) {
            return;
        }

        $statusIn = $old
            ? "'draft', 'active', 'completed', 'archived'"
            : "'draft', 'shooting_completed', 'reshoot', 'processing', 'layout_approval', 'printing', 'completed', 'archived'";

        $pattern = '/"?status"?\s+varchar(?:\([^)]*\))?\s+(?:not\s+null\s+)?(?:default\s+\'[^\']*\'\s+)?(?:check\s*\(\s*"?status"?\s+in\s*\([^)]*\)\s*\)\s+)?not\s+null/i';
        $replacement = '"status" varchar check ("status" in ('.$statusIn.')) not null';

        $tableSql = preg_replace($pattern, $replacement, $current->sql);

        if ($tableSql === null || $tableSql === $current->sql) {
            return;
        }

        $indexes = DB::select("SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = 'projects' AND sql IS NOT NULL");

        $columns = $this->columnNames();
        $statusConversion = $old
            ? "CASE WHEN status IN ('shooting_completed','reshoot','processing','layout_approval','printing') THEN 'active' ELSE status END"
            : "CASE WHEN status = 'active' THEN 'processing' ELSE status END";

        $selectColumns = [];
        foreach ($columns as $column) {
            $selectColumns[] = $column === '"status"' ? "{$statusConversion} AS status" : $column;
        }
        $columnList = implode(', ', $columns);
        $selectList = implode(', ', $selectColumns);

        Schema::disableForeignKeyConstraints();

        DB::transaction(function () use ($tableSql, $indexes, $columnList, $selectList) {
            DB::statement(str_replace('"projects"', '"projects_new"', $tableSql));

            DB::statement("INSERT INTO projects_new ({$columnList}) SELECT {$selectList} FROM projects");

            DB::statement('DROP TABLE projects');
            DB::statement('ALTER TABLE projects_new RENAME TO projects');

            foreach ($indexes as $index) {
                DB::statement($index->sql);
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    private function columnNames(): array
    {
        $columns = DB::select("PRAGMA table_info('projects')");

        return array_map(fn ($column) => '"'.$column->name.'"', $columns);
    }
};
