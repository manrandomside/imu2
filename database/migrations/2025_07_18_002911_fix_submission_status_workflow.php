<?php

// Buat file migration baru: php artisan make:migration fix_submission_status_workflow

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan kolom status di content_submissions menggunakan enum yang benar
        DB::statement("ALTER TABLE content_submissions MODIFY COLUMN status ENUM(
            'pending_payment', 
            'pending_approval', 
            'approved', 
            'published', 
            'rejected'
        ) NOT NULL DEFAULT 'pending_payment'");

        // 2. Pastikan kolom status di payments menggunakan enum yang benar
        if (Schema::hasTable('payments')) {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM(
                'pending', 
                'confirmed', 
                'rejected'
            ) NOT NULL DEFAULT 'pending'");
        }

        // 3. Fix data yang mungkin inconsistent
        // Update submission yang payment sudah confirmed tapi masih pending_payment
        DB::statement("
            UPDATE content_submissions cs 
            INNER JOIN payments p ON cs.id = p.submission_id 
            SET cs.status = 'pending_approval' 
            WHERE cs.status = 'pending_payment' 
            AND p.status = 'confirmed'
        ");

        // 4. Pastikan index untuk performa
        Schema::table('content_submissions', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['submission_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert changes if needed
        Schema::table('content_submissions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['user_id', 'status']);
        });

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex(['submission_id', 'status']);
            });
        }
    }
};