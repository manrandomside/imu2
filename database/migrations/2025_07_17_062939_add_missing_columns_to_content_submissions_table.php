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
        Schema::table('content_submissions', function (Blueprint $table) {
            // Add missing columns
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->onDelete('set null');
            $table->timestamp('published_at')->nullable()->after('rejected_at');
            $table->foreignId('published_by')->nullable()->after('rejected_by')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_submissions', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['published_by']);
            $table->dropColumn(['rejected_at', 'rejected_by', 'published_at', 'published_by']);
        });
    }
};