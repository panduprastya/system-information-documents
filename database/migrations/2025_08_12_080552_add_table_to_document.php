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
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('status', ['pending', 'reviewing', 'approved','rejected'])->default('pending');           
            $table->enum('hsse_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            $table->enum('snd_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            $table->timestamp('hsse_review_started_at')->nullable();
            $table->timestamp('snd_review_started_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document', function (Blueprint $table) {
            //
        });
    }
};
