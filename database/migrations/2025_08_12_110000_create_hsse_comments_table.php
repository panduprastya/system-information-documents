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
        Schema::create('hsse_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('komentar');
            $table->string('notes_reference')->nullable();
            $table->integer('notes_line_number')->nullable();
            $table->text('notes_excerpt')->nullable();
            $table->enum('status_before', ['pending', 'review', 'approved', 'rejected'])->nullable();
            $table->enum('status_after', ['pending', 'review', 'approved', 'rejected'])->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['document_id', 'user_id']);
            $table->index(['document_id', 'is_resolved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hsse_comments');
    }
};
