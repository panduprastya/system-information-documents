<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan index yang hilang setelah rename kolom snd -> crm
     * dan kolom document_type yang sering difilter.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Index untuk document_type (sering dipakai di WHERE clause)
            if (!$this->indexExists('documents', 'documents_document_type_index')) {
                $table->index('document_type', 'documents_document_type_index');
            }

            // Index untuk crm_status (pengganti snd_status yang sudah di-rename)
            if (!$this->indexExists('documents', 'documents_crm_status_index')) {
                $table->index('crm_status', 'documents_crm_status_index');
            }

            // Index untuk id_crm (pengganti id_snd yang sudah di-rename)
            if (!$this->indexExists('documents', 'documents_id_crm_index')) {
                $table->index('id_crm', 'documents_id_crm_index');
            }

            // Composite index: document_type + hsse_status (paling sering diquery bersamaan)
            if (!$this->indexExists('documents', 'documents_type_hsse_status_index')) {
                $table->index(['document_type', 'hsse_status'], 'documents_type_hsse_status_index');
            }

            // Composite index: document_type + crm_status
            if (!$this->indexExists('documents', 'documents_type_crm_status_index')) {
                $table->index(['document_type', 'crm_status'], 'documents_type_crm_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_document_type_index');
            $table->dropIndex('documents_crm_status_index');
            $table->dropIndex('documents_id_crm_index');
            $table->dropIndex('documents_type_hsse_status_index');
            $table->dropIndex('documents_type_crm_status_index');
        });
    }

    /**
     * Cek apakah index sudah ada (hindari error duplikat)
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = config('database.default');
        $database   = config("database.connections.{$connection}.database");

        $count = DB::select("
            SELECT COUNT(*) as cnt
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name   = ?
              AND index_name   = ?
        ", [$database, $table, $index]);

        return $count[0]->cnt > 0;
    }
};
