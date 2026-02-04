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
        Schema::table('documents', function (Blueprint $table) {
            // Indexing foreign keys for faster relationship loading
            $table->index('id_mitra');
            $table->index('id_hsse');
            $table->index('id_snd');

            // Indexing status columns for faster filtering
            $table->index('hsse_status');
            $table->index('snd_status');

            // Indexing created_at because tables usually sort by date
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['id_mitra']);
            $table->dropIndex(['id_hsse']);
            $table->dropIndex(['id_snd']);
            $table->dropIndex(['hsse_status']);
            $table->dropIndex(['snd_status']);
            $table->dropIndex(['created_at']);
        });
    }
};
