<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChatGroup;
use Illuminate\Support\Facades\Hash;

class AdminModeratorSeeder extends Seeder
{
    /**
     * ✅ FINAL: Create Admin and Moderators dengan role yang benar
     */
    public function run()
    {
        $this->command->info('🚀 Starting Admin & Moderator Role Fix...');
        
        $password = 'cussrombyman123'; // Unified password
        
        // ✅ STEP 0: Clean existing wrong data
        $this->command->info('🧹 Cleaning existing wrong role data...');
        
        // Delete users with wrong names/roles/emails
        User::where('full_name', 'LIKE', '%Koordinator%')->delete();
        User::where('full_name', '=', 'Admin IMU')->delete(); // Remove old admin
        User::whereIn('username', ['mod_lomba', 'mod_workshop', 'mod_seminar', 'mod_beasiswa', 'mod_pkm', 'mod_karir'])
            ->where('role', '!=', 'moderator')
            ->delete();
        
        // Delete old email domain accounts
        User::where('email', 'LIKE', '%@unud.ac.id')->delete();
        
        // ✅ RESET community moderator assignments (important!)
        ChatGroup::whereNotNull('moderator_id')->update(['moderator_id' => null]);
        
        // ✅ DELETE unwanted communities
        ChatGroup::where('name', 'Pengumuman Umum')->delete();
        
        $this->command->info('   🗑️ Cleaned wrong role data and old email domains');
        $this->command->info('   🔄 Reset all community moderator assignments');
        $this->command->info('   🗑️ Deleted unwanted communities');
        
        // ✅ STEP 1: Create/Update Super Administrator ONLY
        $admin = User::updateOrCreate(
            ['email' => 'admin@student.unud.ac.id'],
            [
                'full_name' => 'Super Administrator',
                'username' => 'super_admin',
                'password' => Hash::make($password),
                'role' => 'admin', // ✅ CORRECT ROLE
                'is_verified' => true,
                'prodi' => 'Informatika',
                'fakultas' => 'MIPA',
                'gender' => 'Laki-laki',
                'description' => 'Super Administrator dengan akses penuh ke semua komunitas dan fitur sistem',
                'interests' => ['management', 'technology', 'education'],
                'match_categories' => ['admin', 'management']
            ]
        );
        
        // ✅ Delete old admin accounts
        User::where('email', '!=', 'admin@student.unud.ac.id')
            ->where('role', 'admin')
            ->delete();
        
        $this->command->info("✅ Super Administrator created/updated: {$admin->full_name} (Role: {$admin->role})");
        
        // ✅ STEP 2: Force Create/Update Moderators dengan role yang benar
        $moderators = [
            [
                'community_name' => 'PKM',
                'moderator' => [
                    'full_name' => 'Moderator PKM',
                    'username' => 'mod_pkm',
                    'email' => 'mod.pkm@student.unud.ac.id',
                    'prodi' => 'Informatika',
                    'description' => 'Moderator khusus mengelola Program Kreativitas Mahasiswa dan kompetisi akademik'
                ]
            ],
            [
                'community_name' => 'Info Beasiswa',
                'moderator' => [
                    'full_name' => 'Moderator Beasiswa', 
                    'username' => 'mod_beasiswa',
                    'email' => 'mod.beasiswa@student.unud.ac.id',
                    'prodi' => 'Informatika',
                    'description' => 'Moderator untuk mengelola informasi beasiswa dalam dan luar negeri'
                ]
            ],
            [
                'community_name' => 'Lowongan Kerja',
                'moderator' => [
                    'full_name' => 'Moderator Karir',
                    'username' => 'mod_karir',
                    'email' => 'mod.karir@student.unud.ac.id', 
                    'prodi' => 'Informatika',
                    'description' => 'Moderator untuk mengelola lowongan kerja dan informasi karir'
                ]
            ],
            [
                'community_name' => 'Lomba',
                'moderator' => [
                    'full_name' => 'Moderator Lomba',
                    'username' => 'mod_lomba',
                    'email' => 'mod.lomba@student.unud.ac.id',
                    'prodi' => 'Informatika', 
                    'description' => 'Moderator untuk mengelola informasi lomba dan kompetisi'
                ]
            ],
            [
                'community_name' => 'Workshop',
                'moderator' => [
                    'full_name' => 'Moderator Workshop',
                    'username' => 'mod_workshop',
                    'email' => 'mod.workshop@student.unud.ac.id',
                    'prodi' => 'Informatika',
                    'description' => 'Moderator untuk mengelola workshop dan pelatihan'
                ]
            ],
            [
                'community_name' => 'Seminar',
                'moderator' => [
                    'full_name' => 'Moderator Seminar',
                    'username' => 'mod_seminar', 
                    'email' => 'mod.seminar@student.unud.ac.id',
                    'prodi' => 'Informatika',
                    'description' => 'Moderator untuk mengelola seminar dan acara akademik'
                ]
            ]
        ];
        
        $createdModerators = [];
        
        foreach ($moderators as $modData) {
            // Force delete existing dengan email yang sama (both old and new domain)
            User::where('email', $modData['moderator']['email'])->delete();
            User::where('email', str_replace('@student.unud.ac.id', '@unud.ac.id', $modData['moderator']['email']))->delete();
            
            // Create fresh moderator dengan role yang benar
            $moderator = User::create([
                'full_name' => $modData['moderator']['full_name'],
                'username' => $modData['moderator']['username'],
                'email' => $modData['moderator']['email'],
                'password' => Hash::make($password),
                'role' => 'moderator', // ✅ FORCE CORRECT ROLE
                'is_verified' => true,
                'prodi' => $modData['moderator']['prodi'],
                'fakultas' => 'MIPA',
                'gender' => 'Laki-laki',
                'description' => $modData['moderator']['description'],
                'interests' => ['education', 'community', 'leadership'],
                'match_categories' => ['moderator', 'education']
            ]);
            
            $createdModerators[$modData['community_name']] = $moderator;
            $this->command->info("✅ Moderator created: {$moderator->full_name} (Role: {$moderator->role})");
        }
        
        // ✅ STEP 3: Update Community-Moderator Assignments
        $this->command->info('🔗 Updating community-moderator assignments...');
        
        // ✅ Fix specific community assignments
        $communityAssignments = [
            'PKM' => $createdModerators['PKM'],
            'Info Beasiswa' => $createdModerators['Info Beasiswa'], 
            'Lowongan Kerja' => $createdModerators['Lowongan Kerja'],
            'Lomba' => $createdModerators['Lomba'],
            'Workshop' => $createdModerators['Workshop'],
            'Seminar' => $createdModerators['Seminar']
        ];
        
        foreach ($communityAssignments as $communityName => $moderator) {
            // Try multiple variations of community names
            $community = ChatGroup::where('name', $communityName)->first() 
                        ?? ChatGroup::where('name', 'LIKE', "%{$communityName}%")->first();
            
            if ($community) {
                $community->update([
                    'moderator_id' => $moderator->id,
                    'creator_id' => $admin->id, // Admin sebagai creator
                    'is_approved' => true
                ]);
                
                $this->command->info("   🔗 {$community->name} → {$moderator->full_name}");
            } else {
                // Create community if not exists
                $community = ChatGroup::create([
                    'name' => $communityName,
                    'description' => "Informasi dan diskusi seputar {$communityName}",
                    'creator_id' => $admin->id,
                    'moderator_id' => $moderator->id,
                    'is_approved' => true
                ]);
                
                $this->command->info("   ➕ Created & assigned: {$community->name} → {$moderator->full_name}");
            }
        }
        
        // ✅ STEP 4: Ensure all required communities exist
        $this->command->info('📋 Ensuring all required communities exist...');
        
        $requiredCommunities = [
            'PKM' => 'Program Kreativitas Mahasiswa dan kompetisi akademik',
            'Info Beasiswa' => 'Informasi beasiswa dalam dan luar negeri',
            'Lowongan Kerja' => 'Lowongan kerja dan peluang karir',
            'Lomba' => 'Informasi lomba dan kompetisi',
            'Workshop' => 'Workshop dan pelatihan skill development', 
            'Seminar' => 'Seminar dan acara akademik'
        ];
        
        foreach ($requiredCommunities as $name => $description) {
            $community = ChatGroup::where('name', $name)->first();
            $moderator = $createdModerators[$name] ?? null;
            
            if (!$community) {
                $community = ChatGroup::create([
                    'name' => $name,
                    'description' => $description,
                    'creator_id' => $admin->id,
                    'moderator_id' => $moderator ? $moderator->id : null,
                    'is_approved' => true
                ]);
                
                $this->command->info("   ➕ Created community: {$name}");
            } else {
                // Update existing community
                $community->update([
                    'moderator_id' => $moderator ? $moderator->id : null,
                    'creator_id' => $admin->id,
                    'is_approved' => true
                ]);
                
                $this->command->info("   🔄 Updated community: {$name}");
            }
        }
        // ✅ STEP 5: Clean up unwanted communities
        $this->command->info('🗑️ Cleaning up unwanted communities...');
        
        // Delete Pengumuman Umum community
        ChatGroup::where('name', 'Pengumuman Umum')->delete();
        $this->command->info("   🗑️ Deleted: Pengumuman Umum community");
        
        // ✅ STEP 6: Debug - Show final assignments
        $this->command->info('');
        $this->command->info('🔍 FINAL COMMUNITY ASSIGNMENTS:');
        $allCommunities = ChatGroup::with('moderator:id,full_name')->get();
        foreach ($allCommunities as $community) {
            $moderatorName = $community->moderator ? $community->moderator->full_name : 'No moderator (Admin only)';
            $this->command->info("   📋 {$community->name} → {$moderatorName}");
        }
        
        // ✅ STEP 6: Final cleanup - Fix any remaining wrong role users
        $this->command->info('🔧 Final cleanup of wrong role data...');
        
        // Force fix any remaining users with moderator usernames but wrong role
        $wrongRoleUsers = User::whereIn('username', [
            'mod_lomba', 'mod_workshop', 'mod_seminar', 'mod_beasiswa', 'mod_pkm', 'mod_karir'
        ])->where('role', '!=', 'moderator')->get();
        
        foreach ($wrongRoleUsers as $user) {
            $oldRole = $user->role;
            $user->update(['role' => 'moderator']);
            $this->command->info("🔧 Fixed role for: {$user->full_name} (was: {$oldRole}, now: moderator)");
        }
        
        // Also fix any users with "Koordinator" in name
        $koordinatorUsers = User::where('full_name', 'LIKE', '%Koordinator%')->get();
        foreach ($koordinatorUsers as $user) {
            $newName = str_replace('Koordinator', 'Moderator', $user->full_name);
            $user->update([
                'full_name' => $newName,
                'role' => 'moderator'
            ]);
            $this->command->info("🔧 Fixed name & role: {$user->full_name} → {$newName} (moderator)");
        }
        
        // ✅ STEP 7: Summary & Login Info
        $this->command->info('');
        $this->command->info('🎉 ADMIN & MODERATOR SETUP COMPLETED!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('• Admin users: ' . User::where('role', 'admin')->count());
        $this->command->info('• Moderator users: ' . User::where('role', 'moderator')->count());
        $this->command->info('• Total communities: ' . ChatGroup::where('is_approved', true)->count());
        $this->command->info('');
        $this->command->info('🔐 LOGIN CREDENTIALS (Password: cussrombyman123):');
        $this->command->info('');
        $this->command->info('👑 SUPER ADMINISTRATOR (Akses semua komunitas):');
        $this->command->info('   Email: admin@student.unud.ac.id');
        $this->command->info('   Username: super_admin');
        $this->command->info('');
        $this->command->info('🛡️ MODERATORS (Akses komunitas tertentu):');
        foreach ($createdModerators as $communityName => $moderator) {
            $this->command->info("   {$communityName}: {$moderator->email} (username: {$moderator->username})");
        }
        $this->command->info('');
        $this->command->info('🧪 TEST SCENARIOS:');
        $this->command->info('1. Login sebagai super_admin → Bisa posting ke semua komunitas');
        $this->command->info('2. Login sebagai mod_pkm → Hanya bisa posting ke PKM');
        $this->command->info('3. Login sebagai mod_beasiswa → Hanya bisa posting ke Info Beasiswa');
        $this->command->info('4. Login sebagai user biasa → Hanya bisa baca, tidak bisa posting');
        $this->command->info('');
        $this->command->info('🎯 EXPECTED RESULTS:');
        $this->command->info('• Moderator Beasiswa should see: Info Beasiswa community');
        $this->command->info('• Moderator PKM should see: PKM community');
        $this->command->info('• Super Admin should see: ALL 6 communities (no Pengumuman Umum)');
        $this->command->info('• All moderators should see their assigned community in header');
        $this->command->info('');
        $this->command->info('✅ Silakan test community features sekarang!');
    }
}