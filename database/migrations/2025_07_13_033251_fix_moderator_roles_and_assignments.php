<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ✅ SQLite-compatible migration dengan table rebuild untuk add moderator role
     */
    public function up(): void
    {
        // ✅ STEP 1: Get existing data
        $existingUsers = DB::table('users')->get()->toArray();
        
        // ✅ STEP 2: Drop existing table and recreate with new enum
        Schema::dropIfExists('users_backup');
        
        // Create backup table
        Schema::create('users_backup', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('prodi')->nullable();
            $table->string('fakultas')->nullable();
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable();
            $table->text('description')->nullable();
            $table->json('interests')->nullable();
            $table->string('role')->default('mahasiswa'); // Temporary as string
            $table->string('profile_picture')->nullable();
            $table->string('verification_doc_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->json('match_categories')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        
        // ✅ STEP 3: Copy existing data to backup
        foreach ($existingUsers as $user) {
            DB::table('users_backup')->insert((array) $user);
        }
        
        // ✅ STEP 4: Drop and recreate users table with new enum
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('prodi')->nullable();
            $table->string('fakultas')->nullable();
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable();
            $table->text('description')->nullable();
            $table->json('interests')->nullable();
            // ✅ NEW: Updated enum with moderator
            $table->enum('role', ['mahasiswa', 'alumni', 'tenaga_pendidik', 'moderator', 'admin'])->default('mahasiswa');
            $table->string('profile_picture')->nullable();
            $table->string('verification_doc_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->json('match_categories')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            // Add indexes
            $table->index('role');
        });
        
        // ✅ STEP 5: Copy data back and update moderator roles
        foreach ($existingUsers as $user) {
            $userData = (array) $user;
            
            // Update role for moderator users
            if (str_contains($userData['full_name'], 'Moderator')) {
                $userData['role'] = 'moderator';
            }
            
            DB::table('users')->insert($userData);
        }
        
        // ✅ STEP 6: Fix specific moderator assignments
        $moderatorUpdates = [
            2 => ['role' => 'moderator', 'full_name' => 'Moderator PKM'],
            3 => ['role' => 'moderator', 'full_name' => 'Moderator Lomba'],
            4 => ['role' => 'moderator', 'full_name' => 'Moderator Beasiswa'],
            5 => ['role' => 'moderator', 'full_name' => 'Moderator Workshop'],
            6 => ['role' => 'moderator', 'full_name' => 'Moderator Lowongan'],
        ];
        
        foreach ($moderatorUpdates as $userId => $data) {
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user) {
                DB::table('users')->where('id', $userId)->update($data);
                echo "Updated user ID {$userId} to {$data['full_name']}\n";
            }
        }
        
        // ✅ STEP 7: Fix chat_groups moderator assignments
        $groupAssignments = [
            'PKM & Kompetisi' => 2,
            'PKM' => 2,
            'Lomba' => 3,
            'Kompetisi' => 3,
            'Info Beasiswa' => 4,
            'Beasiswa' => 4,
            'Event & Workshop' => 5,
            'Workshop' => 5,
            'Event' => 5,
            'Lowongan Kerja' => 6,
            'Lowongan' => 6,
            'Karir' => 6,
        ];
        
        foreach ($groupAssignments as $groupName => $moderatorId) {
            $groups = DB::table('chat_groups')
                       ->where('name', 'LIKE', '%' . $groupName . '%')
                       ->orWhere('name', $groupName)
                       ->get();
            
            foreach ($groups as $group) {
                DB::table('chat_groups')
                  ->where('id', $group->id)
                  ->update(['moderator_id' => $moderatorId]);
                echo "Assigned group '{$group->name}' to moderator ID {$moderatorId}\n";
            }
        }
        
        // ✅ STEP 8: Create performance indexes
        try {
            if (Schema::hasTable('chat_groups')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_chat_groups_moderator_approved ON chat_groups(moderator_id, is_approved)');
            }
            if (Schema::hasTable('group_messages')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_group_messages_group_created ON group_messages(group_id, created_at)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_group_messages_sender_created ON group_messages(sender_id, created_at)');
            }
            if (Schema::hasTable('post_reactions')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_post_reactions_message_type ON post_reactions(message_id, reaction_type)');
            }
            if (Schema::hasTable('post_comments')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_post_comments_message_parent_created ON post_comments(message_id, parent_id, created_at)');
            }
        } catch (\Exception $e) {
            echo "Warning: Could not create some indexes - " . $e->getMessage() . "\n";
        }
        
        // ✅ STEP 9: Clean up backup table
        Schema::dropIfExists('users_backup');
        
        // ✅ STEP 10: Validation
        $adminCount = DB::table('users')->where('role', 'admin')->count();
        $moderatorCount = DB::table('users')->where('role', 'moderator')->count();
        $assignedGroups = DB::table('chat_groups')->whereNotNull('moderator_id')->count();
        
        echo "\n=== MIGRATION RESULTS ===\n";
        echo "Admin users: {$adminCount}\n";
        echo "Moderator users: {$moderatorCount}\n";
        echo "Groups with assigned moderators: {$assignedGroups}\n";
        echo "Table rebuilt successfully with moderator role!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get current data
        $existingUsers = DB::table('users')->get()->toArray();
        
        // Recreate original table structure
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('prodi')->nullable();
            $table->string('fakultas')->nullable();
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable();
            $table->text('description')->nullable();
            $table->json('interests')->nullable();
            // Original enum without moderator
            $table->enum('role', ['mahasiswa', 'alumni', 'tenaga_pendidik', 'admin'])->default('mahasiswa');
            $table->string('profile_picture')->nullable();
            $table->string('verification_doc_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->json('match_categories')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        
        // Restore data, converting moderator back to mahasiswa
        foreach ($existingUsers as $user) {
            $userData = (array) $user;
            if ($userData['role'] === 'moderator') {
                $userData['role'] = 'mahasiswa';
            }
            DB::table('users')->insert($userData);
        }
        
        // Clear moderator assignments
        DB::table('chat_groups')->update(['moderator_id' => null]);
        
        echo "Migration rolled back successfully\n";
    }
};