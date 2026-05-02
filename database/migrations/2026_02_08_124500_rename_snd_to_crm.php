<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename table snds to crms (only if exists)
        if (Schema::hasTable('snds')) {
            Schema::rename('snds', 'crms');
        }

        // Rename table table_snd_comments to table_crm_comments (only if exists)
        if (Schema::hasTable('table_snd_comments')) {
            Schema::rename('table_snd_comments', 'table_crm_comments');
        }

        // Rename columns in documents table (only if they exist)
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'snd_status')) {
                    $table->renameColumn('snd_status', 'crm_status');
                }
                if (Schema::hasColumn('documents', 'snd_review_started_at')) {
                    $table->renameColumn('snd_review_started_at', 'crm_review_started_at');
                }
                if (Schema::hasColumn('documents', 'id_snd')) {
                    $table->renameColumn('id_snd', 'id_crm');
                }
            });
        }

        // Update role name from 'S&D' or 'SND' to 'CRM' in roles table
        DB::table('roles')
            ->whereIn('name', ['S&D', 'SND', 'snd'])
            ->update(['name' => 'CRM']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse role name update
        DB::table('roles')
            ->where('name', 'CRM')
            ->update(['name' => 'S&D']);

        // Reverse column renames in documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('crm_status', 'snd_status');
            $table->renameColumn('crm_review_started_at', 'snd_review_started_at');
            $table->renameColumn('id_crm', 'id_snd');
        });

        // Reverse table renames
        Schema::rename('table_crm_comments', 'table_snd_comments');
        Schema::rename('crms', 'snds');
    }
};
