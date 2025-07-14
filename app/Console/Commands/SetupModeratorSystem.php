<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ChatGroup;

class SetupModeratorSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moderator:setup 
                            {--fix-existing : Fix existing moderator data}
                            {--create-test : Create test moderators}
                            {--assign-communities : Auto-assign moderators to communities}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup and fix moderator system with proper role assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛡️  Starting Moderator System Setup...');
        $this->newLine();

        // Check if migration is needed
        if (!$this->checkRoleEnum()) {
            $this->error('❌ Role enum does not include "moderator". Please run the migration first.');
            $this->comment('Run: php artisan migrate');
            return Command::FAILURE;
        }

        $operations = [];

        if ($this->option('fix-existing')) {
            $operations[] = 'fix-existing';
        }

        if ($this->option('create-test')) {
            $operations[] = 'create-test';
        }

        if ($this->option('assign-communities')) {
            $operations[] = 'assign-communities';
        }

        // If no specific options, run all operations
        if (empty($operations)) {
            $operations = ['fix-existing', 'create-test', 'assign-communities'];
        }

        // Confirmation
        if (!$this->option('force')) {
            $this->warn('This command will modify user roles and community assignments.');
            $this->table(['Operation', 'Description'], [
                ['fix-existing', 'Update existing users with "Moderator" names to moderator role'],
                ['create-test', 'Create test moderator users if they don\'t exist'],
                ['assign-communities', 'Auto-assign moderators to matching communities'],
            ]);

            if (!$this->confirm('Do you want to continue?')) {
                $this->comment('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $results = [];

        foreach ($operations as $operation) {
            switch ($operation) {
                case 'fix-existing':
                    $results['fix-existing'] = $this->fixExistingModerators();
                    break;
                case 'create-test':
                    $results['create-test'] = $this->createTestModerators();
                    break;
                case 'assign-communities':
                    $results['assign-communities'] = $this->assignCommunities();
                    break;
            }
        }

        $this->displayResults($results);
        $this->showSystemStatus();

        return Command::SUCCESS;
    }

    /**
     * Check if role enum includes moderator
     */
    private function checkRoleEnum(): bool
    {
        try {
            $roleCheck = DB::select("SHOW COLUMNS FROM users LIKE 'role'");
            $roleEnum = $roleCheck[0]->Type ?? '';
            return str_contains($roleEnum, 'moderator');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fix existing users who should be moderators
     */
    private function fixExistingModerators(): array
    {
        $this->info('🔧 Fixing existing moderator users...');

        $results = [
            'updated_users' => 0,
            'users' => []
        ];

        try {
            // Find users with "Moderator" in their name
            $moderatorUsers = User::where('full_name', 'LIKE', '%Moderator%')
                                  ->where('role', '!=', 'moderator')
                                  ->get();

            foreach ($moderatorUsers as $user) {
                $oldRole = $user->role;
                $user->role = 'moderator';
                $user->save();

                $results['updated_users']++;
                $results['users'][] = [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'old_role' => $oldRole,
                    'new_role' => 'moderator'
                ];

                $this->line("   ✅ Updated: {$user->full_name} ({$oldRole} → moderator)");
            }

            // Fix specific user IDs (2-6) if they exist
            $specificModerators = [
                2 => 'Moderator PKM',
                3 => 'Moderator Lomba',
                4 => 'Moderator Beasiswa',
                5 => 'Moderator Workshop',
                6 => 'Moderator Lowongan',
            ];

            foreach ($specificModerators as $userId => $name) {
                $user = User::find($userId);
                if ($user && $user->role !== 'moderator') {
                    $oldRole = $user->role;
                    $user->role = 'moderator';
                    $user->full_name = $name; // Also fix the name
                    $user->save();

                    $results['updated_users']++;
                    $results['users'][] = [
                        'id' => $user->id,
                        'name' => $name,
                        'old_role' => $oldRole,
                        'new_role' => 'moderator'
                    ];

                    $this->line("   ✅ Fixed specific user: ID {$userId} → {$name} (moderator)");
                }
            }

            $this->info("✅ Fixed {$results['updated_users']} existing users");

        } catch (\Exception $e) {
            $this->error("❌ Error fixing existing moderators: " . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Create test moderator users
     */
    private function createTestModerators(): array
    {
        $this->info('👥 Creating test moderator users...');

        $results = [
            'created_users' => 0,
            'existing_users' => 0,
            'users' => []
        ];

        $testModerators = [
            [
                'full_name' => 'Moderator PKM',
                'username' => 'moderator_pkm',
                'email' => 'moderator.pkm@unud.ac.id',
                'prodi' => 'Teknik Informatika',
                'fakultas' => 'Teknik',
                'specialization' => 'PKM & Kompetisi'
            ],
            [
                'full_name' => 'Moderator Beasiswa',
                'username' => 'moderator_beasiswa',
                'email' => 'moderator.beasiswa@unud.ac.id',
                'prodi' => 'Sistem Informasi',
                'fakultas' => 'Teknik',
                'specialization' => 'Info Beasiswa'
            ],
            [
                'full_name' => 'Moderator Workshop',
                'username' => 'moderator_workshop',
                'email' => 'moderator.workshop@unud.ac.id',
                'prodi' => 'Teknik Komputer',
                'fakultas' => 'Teknik',
                'specialization' => 'Event & Workshop'
            ],
            [
                'full_name' => 'Moderator Lowongan',
                'username' => 'moderator_lowongan',
                'email' => 'moderator.lowongan@unud.ac.id',
                'prodi' => 'Manajemen Informatika',
                'fakultas' => 'Teknik',
                'specialization' => 'Lowongan Kerja'
            ],
        ];

        try {
            foreach ($testModerators as $moderatorData) {
                $existingUser = User::where('email', $moderatorData['email'])->first();

                if ($existingUser) {
                    $results['existing_users']++;
                    $this->line("   ⚠️  Already exists: {$moderatorData['full_name']}");
                    continue;
                }

                $user = User::create([
                    'full_name' => $moderatorData['full_name'],
                    'username' => $moderatorData['username'],
                    'email' => $moderatorData['email'],
                    'password' => Hash::make('password123'), // Default password
                    'role' => 'moderator',
                    'prodi' => $moderatorData['prodi'],
                    'fakultas' => $moderatorData['fakultas'],
                    'gender' => 'Laki-laki',
                    'description' => "Moderator untuk {$moderatorData['specialization']}",
                    'is_verified' => true,
                    'interests' => ['moderation', 'community_management'],
                    'match_categories' => ['administration']
                ]);

                $results['created_users']++;
                $results['users'][] = [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'specialization' => $moderatorData['specialization']
                ];

                $this->line("   ✅ Created: {$user->full_name} (ID: {$user->id})");
            }

            $this->info("✅ Created {$results['created_users']} new moderators, {$results['existing_users']} already existed");

        } catch (\Exception $e) {
            $this->error("❌ Error creating test moderators: " . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Auto-assign moderators to communities
     */
    private function assignCommunities(): array
    {
        $this->info('🏘️ Assigning moderators to communities...');

        $results = [
            'assignments' => 0,
            'assignments_list' => [],
            'unassigned_communities' => []
        ];

        $assignments = [
            'PKM' => ['PKM & Kompetisi', 'PKM', 'Kompetisi'],
            'Beasiswa' => ['Info Beasiswa', 'Beasiswa'],
            'Workshop' => ['Event & Workshop', 'Workshop', 'Event'],
            'Lowongan' => ['Lowongan Kerja', 'Lowongan', 'Karir'],
            'Lomba' => ['Lomba', 'Competition'],
        ];

        try {
            foreach ($assignments as $moderatorType => $communityKeywords) {
                // Find moderator
                $moderator = User::where('full_name', 'LIKE', "%Moderator {$moderatorType}%")
                                ->where('role', 'moderator')
                                ->first();

                if (!$moderator) {
                    $this->line("   ⚠️  Moderator for {$moderatorType} not found");
                    continue;
                }

                // Find matching communities
                $communities = ChatGroup::where(function($query) use ($communityKeywords) {
                    foreach ($communityKeywords as $keyword) {
                        $query->orWhere('name', 'LIKE', "%{$keyword}%");
                    }
                })->get();

                foreach ($communities as $community) {
                    $oldModeratorId = $community->moderator_id;
                    $community->moderator_id = $moderator->id;
                    $community->save();

                    $results['assignments']++;
                    $results['assignments_list'][] = [
                        'community' => $community->name,
                        'moderator' => $moderator->full_name,
                        'old_moderator_id' => $oldModeratorId,
                        'new_moderator_id' => $moderator->id
                    ];

                    $this->line("   ✅ Assigned: {$community->name} → {$moderator->full_name}");
                }
            }

            // List unassigned communities
            $unassigned = ChatGroup::whereNull('moderator_id')->where('is_approved', true)->get();
            foreach ($unassigned as $community) {
                $results['unassigned_communities'][] = $community->name;
                $this->line("   ⚠️  Unassigned: {$community->name}");
            }

            $this->info("✅ Made {$results['assignments']} community assignments");

        } catch (\Exception $e) {
            $this->error("❌ Error assigning communities: " . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Display operation results
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 Operation Results:');
        $this->newLine();

        foreach ($results as $operation => $result) {
            $this->comment("--- {$operation} ---");
            
            if (isset($result['error'])) {
                $this->error("❌ Error: " . $result['error']);
                continue;
            }

            switch ($operation) {
                case 'fix-existing':
                    $this->line("Updated users: {$result['updated_users']}");
                    break;
                case 'create-test':
                    $this->line("Created users: {$result['created_users']}");
                    $this->line("Existing users: {$result['existing_users']}");
                    break;
                case 'assign-communities':
                    $this->line("Community assignments: {$result['assignments']}");
                    $this->line("Unassigned communities: " . count($result['unassigned_communities']));
                    break;
            }
            $this->newLine();
        }
    }

    /**
     * Show current system status
     */
    private function showSystemStatus(): void
    {
        $this->info('🎯 Current System Status:');
        $this->newLine();

        try {
            $adminCount = User::where('role', 'admin')->count();
            $moderatorCount = User::where('role', 'moderator')->count();
            $totalCommunities = ChatGroup::where('is_approved', true)->count();
            $assignedCommunities = ChatGroup::whereNotNull('moderator_id')->where('is_approved', true)->count();

            $statusTable = [
                ['Role', 'Count'],
                ['Administrators', $adminCount],
                ['Moderators', $moderatorCount],
                ['Total Communities', $totalCommunities],
                ['Assigned Communities', $assignedCommunities],
                ['Unassigned Communities', $totalCommunities - $assignedCommunities],
            ];

            $this->table($statusTable[0], array_slice($statusTable, 1));

            // Moderator details
            if ($moderatorCount > 0) {
                $this->newLine();
                $this->comment('Moderator Details:');
                
                $moderators = User::where('role', 'moderator')
                                 ->with('moderatedCommunities')
                                 ->get();

                $moderatorTable = [['Name', 'Email', 'Assigned Communities']];
                
                foreach ($moderators as $moderator) {
                    $communities = $moderator->moderatedCommunities->pluck('name')->join(', ') ?: 'None';
                    $moderatorTable[] = [
                        $moderator->full_name,
                        $moderator->email,
                        $communities
                    ];
                }

                $this->table($moderatorTable[0], array_slice($moderatorTable, 1));
            }

            $this->newLine();
            $this->info('✅ Moderator system setup completed successfully!');
            $this->comment('Next steps:');
            $this->line('1. Test login with moderator accounts (password: password123)');
            $this->line('2. Verify permission system in community pages');
            $this->line('3. Check moderator dashboard functionality');

        } catch (\Exception $e) {
            $this->error("❌ Error getting system status: " . $e->getMessage());
        }
    }
}