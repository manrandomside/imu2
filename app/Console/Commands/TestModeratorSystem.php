<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ChatGroup;
use App\Models\GroupMessage;
use App\Http\Controllers\CommunityController;

class TestModeratorSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'moderator:test 
                            {--quick : Run quick tests only}
                            {--comprehensive : Run all tests including permission matrix}
                            {--fix-issues : Automatically fix found issues}
                            {--create-demo-data : Create demo data for testing}';

    /**
     * The console command description.
     */
    protected $description = 'Test and validate the 3-tier moderator role system';

    private $testResults = [];
    private $issuesFound = [];
    private $fixedIssues = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing 3-Tier Moderator Role System...');
        $this->newLine();

        // Initialize test results
        $this->testResults = [
            'database_structure' => false,
            'role_assignments' => false,
            'permission_logic' => false,
            'ui_integration' => false,
            'community_assignments' => false
        ];

        // Run tests based on options
        if ($this->option('create-demo-data')) {
            $this->createDemoData();
        }

        $this->testDatabaseStructure();
        $this->testRoleAssignments();
        $this->testCommunityAssignments();
        $this->testPermissionLogic();

        if ($this->option('comprehensive')) {
            $this->testPermissionMatrix();
            $this->testEdgeCases();
        }

        if ($this->option('fix-issues') && !empty($this->issuesFound)) {
            $this->fixFoundIssues();
        }

        $this->displayTestResults();
        $this->generateTestReport();

        return empty($this->issuesFound) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Test database structure
     */
    private function testDatabaseStructure()
    {
        $this->comment('📋 Testing Database Structure...');

        try {
            // Test role enum
            $roleCheck = DB::select("SHOW COLUMNS FROM users LIKE 'role'");
            $roleEnum = $roleCheck[0]->Type ?? '';
            
            if (!str_contains($roleEnum, 'moderator')) {
                $this->issuesFound[] = 'Role enum does not include moderator';
                $this->error('   ❌ Role enum missing moderator');
                return;
            }
            $this->line('   ✅ Role enum includes moderator');

            // Test moderator_id column in chat_groups
            $hasModeratorColumn = DB::getSchemaBuilder()->hasColumn('chat_groups', 'moderator_id');
            if (!$hasModeratorColumn) {
                $this->issuesFound[] = 'chat_groups table missing moderator_id column';
                $this->error('   ❌ Missing moderator_id column in chat_groups');
                return;
            }
            $this->line('   ✅ chat_groups has moderator_id column');

            // Test additional tables
            $requiredTables = ['post_reactions', 'post_comments'];
            foreach ($requiredTables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $this->issuesFound[] = "Missing table: {$table}";
                    $this->error("   ❌ Missing table: {$table}");
                } else {
                    $this->line("   ✅ Table {$table} exists");
                }
            }

            $this->testResults['database_structure'] = empty($this->issuesFound);

        } catch (\Exception $e) {
            $this->issuesFound[] = 'Database structure test failed: ' . $e->getMessage();
            $this->error('   ❌ Database structure test failed');
        }
    }

    /**
     * Test role assignments
     */
    private function testRoleAssignments()
    {
        $this->comment('👥 Testing Role Assignments...');

        try {
            // Count users by role
            $roleCounts = [
                'admin' => User::where('role', 'admin')->count(),
                'moderator' => User::where('role', 'moderator')->count(),
                'mahasiswa' => User::where('role', 'mahasiswa')->count(),
                'alumni' => User::where('role', 'alumni')->count(),
                'tenaga_pendidik' => User::where('role', 'tenaga_pendidik')->count(),
            ];

            $this->table(['Role', 'Count'], [
                ['Admin', $roleCounts['admin']],
                ['Moderator', $roleCounts['moderator']],
                ['Mahasiswa', $roleCounts['mahasiswa']],
                ['Alumni', $roleCounts['alumni']],
                ['Tenaga Pendidik', $roleCounts['tenaga_pendidik']],
            ]);

            // Validate minimum requirements
            if ($roleCounts['admin'] === 0) {
                $this->issuesFound[] = 'No admin users found';
                $this->error('   ❌ No admin users found');
            } else {
                $this->line("   ✅ Found {$roleCounts['admin']} admin(s)");
            }

            if ($roleCounts['moderator'] === 0) {
                $this->issuesFound[] = 'No moderator users found';
                $this->warn('   ⚠️  No moderator users found');
            } else {
                $this->line("   ✅ Found {$roleCounts['moderator']} moderator(s)");
            }

            // Test moderator users specifically
            $moderators = User::where('role', 'moderator')->get();
            foreach ($moderators as $moderator) {
                if (!$moderator->isModerator()) {
                    $this->issuesFound[] = "User {$moderator->full_name} has moderator role but isModerator() returns false";
                    $this->error("   ❌ Role method mismatch for {$moderator->full_name}");
                } else {
                    $this->line("   ✅ {$moderator->full_name} role methods working correctly");
                }
            }

            $this->testResults['role_assignments'] = true;

        } catch (\Exception $e) {
            $this->issuesFound[] = 'Role assignment test failed: ' . $e->getMessage();
            $this->error('   ❌ Role assignment test failed');
        }
    }

    /**
     * Test community assignments
     */
    private function testCommunityAssignments()
    {
        $this->comment('🏘️ Testing Community Assignments...');

        try {
            $totalCommunities = ChatGroup::where('is_approved', true)->count();
            $assignedCommunities = ChatGroup::whereNotNull('moderator_id')
                                            ->where('is_approved', true)
                                            ->count();
            $unassignedCommunities = $totalCommunities - $assignedCommunities;

            $this->table(['Status', 'Count'], [
                ['Total Communities', $totalCommunities],
                ['Assigned Communities', $assignedCommunities],
                ['Unassigned Communities', $unassignedCommunities],
            ]);

            // List community assignments
            $assignments = ChatGroup::whereNotNull('moderator_id')
                                   ->where('is_approved', true)
                                   ->with('moderator:id,full_name,role')
                                   ->get();

            if ($assignments->count() > 0) {
                $assignmentTable = [['Community', 'Moderator', 'Moderator Role']];
                foreach ($assignments as $assignment) {
                    $assignmentTable[] = [
                        $assignment->name,
                        $assignment->moderator->full_name ?? 'Unknown',
                        $assignment->moderator->role ?? 'Unknown'
                    ];
                }
                $this->table($assignmentTable[0], array_slice($assignmentTable, 1));
            }

            // Validate assignments
            foreach ($assignments as $assignment) {
                if (!$assignment->moderator) {
                    $this->issuesFound[] = "Community {$assignment->name} has invalid moderator_id";
                    $this->error("   ❌ Invalid moderator assignment for {$assignment->name}");
                } elseif (!$assignment->moderator->hasModeratorPrivileges()) {
                    $this->issuesFound[] = "Community {$assignment->name} assigned to non-moderator user";
                    $this->error("   ❌ {$assignment->name} assigned to non-moderator: {$assignment->moderator->full_name}");
                } else {
                    $this->line("   ✅ {$assignment->name} correctly assigned to {$assignment->moderator->full_name}");
                }
            }

            if ($unassignedCommunities > 0) {
                $this->warn("   ⚠️  {$unassignedCommunities} communities have no moderator assigned");
            }

            $this->testResults['community_assignments'] = true;

        } catch (\Exception $e) {
            $this->issuesFound[] = 'Community assignment test failed: ' . $e->getMessage();
            $this->error('   ❌ Community assignment test failed');
        }
    }

    /**
     * Test permission logic
     */
    private function testPermissionLogic()
    {
        $this->comment('🔐 Testing Permission Logic...');

        try {
            $testUsers = [
                User::where('role', 'admin')->first(),
                User::where('role', 'moderator')->first(),
                User::where('role', 'mahasiswa')->first(),
            ];

            $testCommunity = ChatGroup::where('is_approved', true)->first();

            if (!$testCommunity) {
                $this->issuesFound[] = 'No approved community found for testing';
                $this->error('   ❌ No approved community found for testing');
                return;
            }

            $controller = new CommunityController();

            foreach ($testUsers as $user) {
                if (!$user) continue;

                // Test permission methods
                $permissions = $user->getUserPermissions($testCommunity);
                
                $this->line("   Testing permissions for {$user->full_name} ({$user->role}):");
                
                // Validate admin permissions
                if ($user->isAdmin()) {
                    if (!$testCommunity->canUserPost($user)) {
                        $this->issuesFound[] = "Admin {$user->full_name} cannot post to community";
                        $this->error("     ❌ Admin should be able to post");
                    } else {
                        $this->line("     ✅ Admin can post");
                    }
                }

                // Validate moderator permissions
                if ($user->isModerator()) {
                    $canModerate = $testCommunity->canUserModerate($user);
                    $isAssigned = $testCommunity->moderator_id === $user->id;
                    
                    if ($isAssigned && !$canModerate) {
                        $this->issuesFound[] = "Assigned moderator {$user->full_name} cannot moderate";
                        $this->error("     ❌ Assigned moderator should be able to moderate");
                    } elseif ($isAssigned) {
                        $this->line("     ✅ Assigned moderator can moderate");
                    } else {
                        $this->line("     ⚠️  Moderator not assigned to this community");
                    }
                }

                // Validate regular user permissions
                if ($user->isRegularUser()) {
                    if ($testCommunity->canUserPost($user)) {
                        $this->issuesFound[] = "Regular user {$user->full_name} can post (should not be allowed)";
                        $this->error("     ❌ Regular user should not be able to post");
                    } else {
                        $this->line("     ✅ Regular user correctly cannot post");
                    }
                }
            }

            $this->testResults['permission_logic'] = true;

        } catch (\Exception $e) {
            $this->issuesFound[] = 'Permission logic test failed: ' . $e->getMessage();
            $this->error('   ❌ Permission logic test failed');
        }
    }

    /**
     * Test comprehensive permission matrix
     */
    private function testPermissionMatrix()
    {
        $this->comment('📊 Testing Comprehensive Permission Matrix...');

        try {
            $roles = ['admin', 'moderator', 'mahasiswa', 'alumni', 'tenaga_pendidik'];
            $communities = ChatGroup::where('is_approved', true)->limit(3)->get();

            $permissionMatrix = [];

            foreach ($roles as $role) {
                $user = User::where('role', $role)->first();
                if (!$user) continue;

                foreach ($communities as $community) {
                    $permissions = $community->getUserPermissions($user);
                    $permissionMatrix[] = [
                        'Role' => $role,
                        'Community' => Str::limit($community->name, 20),
                        'Can Read' => $permissions['can_read'] ? '✅' : '❌',
                        'Can Post' => $permissions['can_post'] ? '✅' : '❌',
                        'Can Moderate' => $permissions['can_moderate'] ? '✅' : '❌',
                    ];
                }
            }

            if (!empty($permissionMatrix)) {
                $this->table(['Role', 'Community', 'Can Read', 'Can Post', 'Can Moderate'], $permissionMatrix);
            }

        } catch (\Exception $e) {
            $this->warn('   ⚠️  Permission matrix test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test edge cases
     */
    private function testEdgeCases()
    {
        $this->comment('🎯 Testing Edge Cases...');

        try {
            // Test user with no communities
            $orphanModerator = User::where('role', 'moderator')
                                  ->whereDoesntHave('moderatedCommunities')
                                  ->first();
            
            if ($orphanModerator) {
                $this->warn("   ⚠️  Moderator {$orphanModerator->full_name} has no assigned communities");
            }

            // Test communities with invalid moderator IDs
            $invalidAssignments = ChatGroup::whereNotNull('moderator_id')
                                          ->whereDoesntHave('moderator')
                                          ->get();
            
            foreach ($invalidAssignments as $community) {
                $this->issuesFound[] = "Community {$community->name} has invalid moderator_id: {$community->moderator_id}";
                $this->error("   ❌ Invalid moderator_id in {$community->name}");
            }

            // Test user role consistency
            $inconsistentUsers = User::where('role', 'moderator')
                                    ->where('full_name', 'NOT LIKE', '%Moderator%')
                                    ->get();
            
            foreach ($inconsistentUsers as $user) {
                $this->warn("   ⚠️  User {$user->full_name} has moderator role but non-standard name");
            }

        } catch (\Exception $e) {
            $this->warn('   ⚠️  Edge case testing failed: ' . $e->getMessage());
        }
    }

    /**
     * Create demo data for testing
     */
    private function createDemoData()
    {
        $this->info('🎭 Creating demo data for testing...');

        try {
            // Create demo admin if doesn't exist
            $admin = User::where('role', 'admin')->first();
            if (!$admin) {
                $admin = User::create([
                    'full_name' => 'Demo Admin',
                    'username' => 'demo_admin',
                    'email' => 'admin@demo.test',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'is_verified' => true,
                    'prodi' => 'Teknik Informatika',
                    'fakultas' => 'Teknik',
                    'gender' => 'Laki-laki'
                ]);
                $this->line('   ✅ Created demo admin');
            }

            // Create demo communities if none exist
            $communityCount = ChatGroup::count();
            if ($communityCount === 0) {
                $demoCommunities = [
                    'Demo PKM & Kompetisi',
                    'Demo Info Beasiswa',
                    'Demo Event & Workshop'
                ];

                foreach ($demoCommunities as $name) {
                    ChatGroup::create([
                        'name' => $name,
                        'description' => "Demo community untuk testing: {$name}",
                        'creator_id' => $admin->id,
                        'is_approved' => true
                    ]);
                }
                $this->line('   ✅ Created demo communities');
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Failed to create demo data: ' . $e->getMessage());
        }
    }

    /**
     * Fix found issues automatically
     */
    private function fixFoundIssues()
    {
        $this->comment('🔧 Attempting to fix found issues...');

        foreach ($this->issuesFound as $issue) {
            try {
                if (str_contains($issue, 'invalid moderator_id')) {
                    // Fix invalid moderator assignments
                    $communities = ChatGroup::whereNotNull('moderator_id')
                                           ->whereDoesntHave('moderator')
                                           ->get();
                    
                    foreach ($communities as $community) {
                        $community->moderator_id = null;
                        $community->save();
                        $this->fixedIssues[] = "Cleared invalid moderator_id for {$community->name}";
                    }
                }

                if (str_contains($issue, 'assigned to non-moderator user')) {
                    // Fix non-moderator assignments
                    $assignments = ChatGroup::whereNotNull('moderator_id')
                                           ->whereHas('moderator', function($query) {
                                               $query->whereNotIn('role', ['admin', 'moderator']);
                                           })
                                           ->get();
                    
                    foreach ($assignments as $community) {
                        $community->moderator_id = null;
                        $community->save();
                        $this->fixedIssues[] = "Cleared non-moderator assignment for {$community->name}";
                    }
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Failed to fix issue: {$issue}");
            }
        }

        if (!empty($this->fixedIssues)) {
            $this->info('✅ Fixed issues:');
            foreach ($this->fixedIssues as $fix) {
                $this->line("   • {$fix}");
            }
        }
    }

    /**
     * Display test results
     */
    private function displayTestResults()
    {
        $this->newLine();
        $this->info('📊 Test Results Summary:');
        $this->newLine();

        $passed = 0;
        $total = count($this->testResults);

        foreach ($this->testResults as $test => $result) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            $this->line("   {$status} " . str_replace('_', ' ', ucfirst($test)));
            if ($result) $passed++;
        }

        $this->newLine();
        $this->line("Tests Passed: {$passed}/{$total}");
        
        if (!empty($this->issuesFound)) {
            $this->warn("Issues Found: " . count($this->issuesFound));
            foreach ($this->issuesFound as $issue) {
                $this->line("   • {$issue}");
            }
        } else {
            $this->info('🎉 All tests passed! System is working correctly.');
        }
    }

    /**
     * Generate detailed test report
     */
    private function generateTestReport()
    {
        $this->newLine();
        $this->info('📄 Test Report Generated:');
        $this->newLine();

        $report = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'system_status' => empty($this->issuesFound) ? 'HEALTHY' : 'ISSUES_FOUND',
            'total_users' => User::count(),
            'role_distribution' => [
                'admin' => User::where('role', 'admin')->count(),
                'moderator' => User::where('role', 'moderator')->count(),
                'regular_users' => User::whereIn('role', ['mahasiswa', 'alumni', 'tenaga_pendidik'])->count(),
            ],
            'community_stats' => [
                'total' => ChatGroup::where('is_approved', true)->count(),
                'assigned' => ChatGroup::whereNotNull('moderator_id')->where('is_approved', true)->count(),
                'unassigned' => ChatGroup::whereNull('moderator_id')->where('is_approved', true)->count(),
            ],
            'test_results' => $this->testResults,
            'issues_found' => $this->issuesFound,
            'issues_fixed' => $this->fixedIssues
        ];

        // Save report to storage/logs
        $reportPath = storage_path('logs/moderator_system_test_' . now()->format('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        $this->comment("Detailed report saved to: {$reportPath}");
        
        $this->table(['Metric', 'Value'], [
            ['System Status', $report['system_status']],
            ['Total Users', $report['total_users']],
            ['Admin Users', $report['role_distribution']['admin']],
            ['Moderator Users', $report['role_distribution']['moderator']],
            ['Regular Users', $report['role_distribution']['regular_users']],
            ['Total Communities', $report['community_stats']['total']],
            ['Assigned Communities', $report['community_stats']['assigned']],
            ['Unassigned Communities', $report['community_stats']['unassigned']],
        ]);
    }
}