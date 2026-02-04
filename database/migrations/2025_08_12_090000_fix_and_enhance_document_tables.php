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
        // Fix the komentars table foreign key first
        Schema::table('komentars', function (Blueprint $table) {
            if (Schema::hasColumn('komentars', 'dokumen_id')) {
                // $table->dropForeign(['dokumen_id']); // SQLite might issue with this if not named specifically
                $table->renameColumn('dokumen_id', 'document_id');
                $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            }

            // Add new columns for review tracking
            if (!Schema::hasColumn('komentars', 'reviewer_type')) {
                $table->enum('reviewer_type', ['hsse', 'snd'])->nullable()->after('komentar');
            }
            if (!Schema::hasColumn('komentars', 'status_before')) {
                $table->enum('status_before', ['review', 'revisi', 'approved'])->nullable()->after('reviewer_type');
            }
            if (!Schema::hasColumn('komentars', 'status_after')) {
                $table->enum('status_after', ['review', 'revisi', 'approved'])->nullable()->after('status_before');
            }
        });

        // Add review tracking columns to documents table
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'hsse_status')) {
                $table->enum('hsse_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('documents', 'snd_status')) {
                $table->enum('snd_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending')->after('hsse_status');
            }
            if (!Schema::hasColumn('documents', 'hsse_review_started_at')) {
                $table->timestamp('hsse_review_started_at')->nullable()->after('tanggal_acc');
            }
            if (!Schema::hasColumn('documents', 'snd_review_started_at')) {
                $table->timestamp('snd_review_started_at')->nullable()->after('hsse_review_started_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('komentars', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->renameColumn('document_id', 'dokumen_id');
            $table->foreign('dokumen_id')->references('id')->on('documents')->onDelete('cascade');
            $table->dropColumn(['reviewer_type', 'status_before', 'status_after']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['hsse_status', 'snd_status', 'hsse_review_started_at', 'snd_review_started_at']);
        });
    }
};
