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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Penerima notifikasi
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade'); // Pengirim like
            $table->enum('type', ['like_received', 'match_created', 'message_received']); // Tipe notifikasi
            $table->string('title'); // "A1 menyukai Anda!"
            $table->text('message'); // "Like balik untuk match 💖"
            $table->json('data')->nullable(); // Extra data (user info, match_id, etc)
            $table->boolean('is_read')->default(false); // Status baca
            $table->timestamp('read_at')->nullable(); // Waktu dibaca
            $table->timestamps();

            // Index untuk performance
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};