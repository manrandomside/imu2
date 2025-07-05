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
        Schema::table('users', function (Blueprint $table) {
            // Kolom ini akan menyimpan kategori match utama yang dipilih user (Friends, Jobs, PKM, dll.)
            // Disimpan sebagai JSON array (misal: ["friends", "jobs"])
            $table->json('match_categories')->nullable()->after('interests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('match_categories');
        });
    }
};
