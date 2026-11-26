<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'UPDATE analytics SET recommendations = JSON_ARRAY(recommendations) '
                .'WHERE recommendations IS NOT NULL AND JSON_VALID(recommendations) = 0'
            );
        }

        Schema::table('analytics', function (Blueprint $table) {
            $table->json('recommendations')->nullable()->change();
            $table->index(['field_id', 'analysis_date']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('field_crops', function (Blueprint $table) {
            $table->index(['field_id', 'status']);
        });
    }

    public function down(): void
    {
        $this->ensureForeignKeyIndex(table: 'field_crops', column: 'field_id');
        $this->ensureForeignKeyIndex(table: 'reports', column: 'user_id');
        $this->ensureForeignKeyIndex(table: 'analytics', column: 'field_id');

        Schema::table('field_crops', function (Blueprint $table) {
            $table->dropIndex(['field_id', 'status']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('analytics', function (Blueprint $table) {
            $table->dropIndex(['field_id', 'analysis_date']);
            $table->text('recommendations')->nullable()->change();
        });
    }

    private function ensureForeignKeyIndex(string $table, string $column): void
    {
        if (Schema::hasIndex($table, [$column])) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->index($column);
        });
    }
};
