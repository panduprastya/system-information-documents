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
            if (!Schema::hasColumn('documents', 'status')) {
                $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            }
            if (!Schema::hasColumn('documents', 'hsse_status')) {
                $table->enum('hsse_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            }
            if (!Schema::hasColumn('documents', 'snd_status')) {
                $table->enum('snd_status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            }
            if (!Schema::hasColumn('documents', 'hsse_review_started_at')) {
                $table->timestamp('hsse_review_started_at')->nullable();
            }
            if (!Schema::hasColumn('documents', 'snd_review_started_at')) {
                $table->timestamp('snd_review_started_at')->nullable();
            }
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
