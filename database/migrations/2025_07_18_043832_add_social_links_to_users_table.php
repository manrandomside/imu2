<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan kolom social_links untuk menyimpan LinkedIn, GitHub, Instagram
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom JSON untuk menyimpan social media links
            $table->json('social_links')->nullable()->after('interests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });
    }
};