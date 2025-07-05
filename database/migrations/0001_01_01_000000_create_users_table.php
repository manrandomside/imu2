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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name'); // Mengganti 'name' dengan 'full_name'
            $table->string('username')->unique(); // Menambahkan 'username' sebagai kolom unik
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Menambahkan kolom-kolom profil lainnya sesuai SKPL dan diskusi
            $table->string('prodi')->nullable();
            $table->string('fakultas')->nullable();
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable();
            $table->text('description')->nullable();
            $table->json('interests')->nullable(); // Untuk menyimpan minat dalam format JSON
            $table->enum('role', ['mahasiswa', 'alumni', 'tenaga_pendidik', 'admin'])->default('mahasiswa'); // Peran pengguna
            $table->string('profile_picture')->nullable(); // Path ke foto profil
            $table->string('verification_doc_path')->nullable(); // Path ke dokumen verifikasi alumni
            $table->boolean('is_verified')->default(false); // Status verifikasi akun

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};