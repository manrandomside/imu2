<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // ❌ COMMENTED OUT: Ini menggunakan factory yang tidak sesuai dengan struktur User model Anda
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // ✅ UPDATED: Complete seeding sequence
        $this->call([
            ChatGroupSeeder::class,           // Create communities & basic setup
            AdminModeratorSeeder::class,      // Fix admin/moderator roles & assignments
        ]);

        // ℹ️ INFO: 
        // - ChatGroupSeeder akan otomatis membuat admin user jika belum ada
        // - CommunitySeeder akan membuat komunitas lengkap dengan moderator dan sample posts
        // Jadi tidak perlu factory di sini
    }
}