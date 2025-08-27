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
        // Fix the komentars table foreign key first
        Schema::table('komentars', function (Blueprint $table) {
            $table->dropForeign(['dokumen_id']);
            $table->renameColumn('dokumen_id', 'document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            
            // Add new columns for review tracking
            $table->enum('reviewer_type', ['hsse', 'snd'])->nullable()->after('komentar');
            $table->enum('status_before', ['review', 'revisi', 'approved'])->nullable()->after('reviewer_type');
            $table->enum('status_after', ['review', 'revisi', 'approved'])->nullable()->after('status_before');
        });

        // Add review tracking columns to documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('hsse_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending')->after('status');
            $table->enum('snd_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending')->after('hsse_status');
            $table->timestamp('hsse_review_started_at')->nullable()->after('tanggal_acc');
            $table->timestamp('snd_review_started_at')->nullable()->after('hsse_review_started_at');
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
