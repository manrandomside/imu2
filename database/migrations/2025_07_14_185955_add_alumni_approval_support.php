<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration ini memastikan kolom yang diperlukan sudah ada
     * Seharusnya sudah ada di migration sebelumnya, tapi ini untuk memastikan
     */
    public function up(): void
    {
        // Cek apakah kolom is_verified sudah ada
        if (!Schema::hasColumn('users', 'is_verified')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_verified')->default(false);
            });
        }

        // Cek apakah kolom verification_doc_path sudah ada  
        if (!Schema::hasColumn('users', 'verification_doc_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('verification_doc_path')->nullable();
            });
        }

        // Pastikan role alumni ada di enum
        // Ini akan error jika enum belum support alumni, tapi seharusnya sudah ada
        
        // Set default semua alumni yang belum verified jadi false
        DB::table('users')
            ->where('role', 'alumni')
            ->whereNull('is_verified')
            ->update(['is_verified' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena kolom-kolom ini penting
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn('is_verified');
        //     $table->dropColumn('verification_doc_path');
        // });
    }
};