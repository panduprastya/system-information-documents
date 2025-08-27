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
        Schema::create('komentars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // HSSE atau SND
            $table->text('komentar');
            $table->enum('reviewer_type', ['hsse', 'snd'])->nullable(); // Track reviewer type
            $table->enum('status_before', ['review', 'revisi', 'approved'])->nullable(); // Status before comment
            $table->enum('status_after', ['review', 'revisi', 'approved'])->nullable(); // Status after comment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komentars');
    }
};
