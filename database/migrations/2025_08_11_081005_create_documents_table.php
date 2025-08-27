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
        Schema::create('documents', function (Blueprint $table) {  
        $table->id();
        $table->string('judul_dokumen');
        $table->foreignId('id_mitra')->constrained('users')->onDelete('cascade');
        $table->binary('file');
        $table->enum('status', ['review', 'revisi', 'approved'])->default('review');
        $table->enum('hsse_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
        $table->enum('snd_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
        $table->timestamp('tanggal_upload')->nullable();
        $table->timestamp('tanggal_acc')->nullable();
        $table->timestamp('hsse_review_started_at')->nullable();
        $table->timestamp('snd_review_started_at')->nullable();
        $table->foreignId('id_hsse')->nullable()->constrained('users')->onDelete('set null');
        $table->foreignId('id_snd')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
    });
     }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
