<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnsToDrop = [
            'notes_reference',
            'notes_line_number',
            'notes_excerpt',
            'status_before',
            'is_resolved',
            'resolved_at',
            'resolved_by',
        ];

        if (Schema::hasTable('table_crm_comments')) {
            Schema::table('table_crm_comments', function (Blueprint $table) use ($columnsToDrop) {
                if (Schema::hasColumn('table_crm_comments', 'resolved_by')) {
                    $table->dropForeign(['resolved_by']);
                }
                foreach ($columnsToDrop as $col) {
                    if (Schema::hasColumn('table_crm_comments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('hsse_comments')) {
            Schema::table('hsse_comments', function (Blueprint $table) use ($columnsToDrop) {
                if (Schema::hasColumn('hsse_comments', 'resolved_by')) {
                    $table->dropForeign(['resolved_by']);
                }
                foreach ($columnsToDrop as $col) {
                    if (Schema::hasColumn('hsse_comments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add them back if rolled back
        if (Schema::hasTable('table_crm_comments')) {
            Schema::table('table_crm_comments', function (Blueprint $table) {
                $table->string('notes_reference')->nullable();
                $table->integer('notes_line_number')->nullable();
                $table->text('notes_excerpt')->nullable();
                $table->string('status_before')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            });
        }

        if (Schema::hasTable('hsse_comments')) {
            Schema::table('hsse_comments', function (Blueprint $table) {
                $table->string('notes_reference')->nullable();
                $table->integer('notes_line_number')->nullable();
                $table->text('notes_excerpt')->nullable();
                $table->string('status_before')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            });
        }
    }
};
