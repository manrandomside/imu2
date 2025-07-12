<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ChatGroup;
use App\Models\User;
use App\Models\GroupMessage;

class ChatGroupSeeder extends Seeder
{
    /**
     * ✅ ENHANCED: Run the database seeds untuk Phase 1
     */
    public function run(): void
    {
        // Pastikan ada user admin
        $adminUser = User::where('role', 'admin')->first();
        
        if (!$adminUser) {
            $adminUser = User::first();
            if (!$adminUser) {
                $adminUser = User::create([
                    'full_name' => 'Admin IMU',
                    'username' => 'admin_imu',
                    'email' => 'admin@student.unud.ac.id',
                    'password' => bcrypt('password'),
                    'role' => 'admin',
                    'is_verified' => true,
                    'prodi' => 'Teknik Informatika',
                    'fakultas' => 'Teknik',
                    'gender' => 'Laki-laki',
                    'description' => 'Administrator sistem IMU',
                    'interests' => ['technology', 'education'],
                    'match_categories' => ['jobs', 'committee']
                ]);
            }
        }

        // ✅ ENHANCED: Data komunitas dengan moderator system
        $communities = [
            [
                'name' => 'Lomba',
                'description' => 'Informasi kompetisi, lomba, dan kontes untuk mahasiswa Universitas Udayana.',
                'creator_id' => $adminUser->id,
                'moderator_data' => [
                    'name' => 'Koordinator Lomba',
                    'username' => 'koord_lomba',
                    'email' => 'koordinator.lomba@student.unud.ac.id',
                    'prodi' => 'Teknik Informatika',
                    'fakultas' => 'Teknik',
                ],
                'is_approved' => true,
                'sample_posts' => [
                    [
                        'content' => '🏆 KOMPETISI NASIONAL: Lomba Karya Tulis Ilmiah tingkat nasional dengan total hadiah 50 juta rupiah. Deadline: 15 Februari 2025.',
                        'days_ago' => 2
                    ],
                    [
                        'content' => '💻 Hackathon "Innovation for Indonesia" akan diselenggarakan bulan depan. Daftarkan tim terbaikmu sekarang!',
                        'days_ago' => 5
                    ],
                    [
                        'content' => '🎉 Selamat untuk tim Debat Udayana yang meraih juara 1 di Kompetisi Debat Nasional 2024!',
                        'days_ago' => 10
                    ]
                ]
            ],
            [
                'name' => 'Workshop',
                'description' => 'Workshop, pelatihan, dan kegiatan pengembangan skill untuk mahasiswa.',
                'creator_id' => $adminUser->id,
                'moderator_data' => [
                    'name' => 'Koordinator Workshop',
                    'username' => 'koord_workshop',
                    'email' => 'koordinator.workshop@student.unud.ac.id',
                    'prodi' => 'Sistem Informasi',
                    'fakultas' => 'Teknik',
                ],
                'is_approved' => true,
                'sample_posts' => [
                    [
                        'content' => '🛠️ WORKSHOP: "Digital Marketing for Beginners" - 20 Januari 2025, Gedung Rektorat Lt.2. Gratis untuk 50 peserta pertama!',
                        'days_ago' => 1
                    ],
                    [
                        'content' => '📊 Pelatihan Microsoft Excel Advanced akan diadakan minggu depan. Cocok untuk mahasiswa yang ingin improve data analysis skills.',
                        'days_ago' => 3
                    ],
                    [
                        'content' => '🎤 Workshop "Public Speaking & Presentation Skills" berhasil diselenggarakan dengan 100+ peserta antusias!',
                        'days_ago' => 7
                    ]
                ]
            ],
            [
                'name' => 'Seminar',
                'description' => 'Seminar nasional, webinar, dan talk show dengan pembicara expert.',
                'creator_id' => $adminUser->id,
                'moderator_data' => [
                    'name' => 'Koordinator Seminar',
                    'username' => 'koord_seminar',
                    'email' => 'koordinator.seminar@student.unud.ac.id',
                    'prodi' => 'Teknik Elektro',
                    'fakultas' => 'Teknik',
                ],
                'is_approved' => true,
                'sample_posts' => [
                    [
                        'content' => '🎤 SEMINAR NASIONAL: "Future of Artificial Intelligence in Indonesia" - 25 Januari 2025. Pembicara: CEO startup AI terkemuka.',
                        'days_ago' => 1
                    ],
                    [
                        'content' => '💼 Webinar "Career Preparation for Fresh Graduate" bersama HRD dari berbagai perusahaan multinasional.',
                        'days_ago' => 4
                    ],
                    [
                        'content' => '🚀 Talk Show: "Entrepreneurship Journey" dengan founder-founder startup unicorn Indonesia. Don\'t miss it!',
                        'days_ago' => 6
                    ]
                ]
            ],
            [
                'name' => 'Info Beasiswa',
                'description' => 'Informasi beasiswa dalam dan luar negeri untuk mahasiswa Udayana.',
                'creator_id' => $adminUser->id,
                'moderator_data' => [
                    'name' => 'Koordinator Beasiswa',
                    'username' => 'koord_beasiswa',
                    'email' => 'koordinator.beasiswa@student.unud.ac.id',
                    'prodi' => 'Ekonomi',
                    'fakultas' => 'Ekonomi dan Bisnis',
                ],
                'is_approved' => true,
                'sample_posts' => [
                    [
                        'content' => '🎓 BEASISWA LPDP 2025: Pendaftaran dibuka untuk program S2/S3 dalam dan luar negeri. Persiapkan dokumen dari sekarang!',
                        'days_ago' => 1
                    ],
                    [
                        'content' => '🇩🇪 Beasiswa penuh ke Jerman untuk jurusan Engineering dan Computer Science. Deadline: 31 Maret 2025.',
                        'days_ago' => 5
                    ],
                    [
                        'content' => '💰 Info Beasiswa internal Udayana semester genap 2025 sudah dirilis. Cek syarat dan ketentuan di website resmi.',
                        'days_ago' => 8
                    ]
                ]
            ],
            [
                'name' => 'PKM',
                'description' => 'Program Kreativitas Mahasiswa, riset, dan kegiatan akademik inovatif.',
                'creator_id' => $adminUser->id,
                'moderator_data' => [
                    'name' => 'Koordinator PKM',
                    'username' => 'koord_pkm',
                    'email' => 'koordinator.pkm@student.unud.ac.id',
                    'prodi' => 'Biologi',
                    'fakultas' => 'MIPA',
                ],
                'is_approved' => true,
                'sample_posts' => [
                    [
                        'content' => '🔬 DEADLINE PKM 2025 diperpanjang hingga 20 Februari! Tim yang belum submit segera finalisasi proposal.',
                        'days_ago' => 2
                    ],
                    [
                        'content' => '📝 Workshop "How to Write Winning PKM Proposal" akan diadakan untuk membantu mahasiswa menyusun proposal yang berkualitas.',
                        'days_ago' => 6
                    ],
                    [
                        'content' => '🎉 Selamat untuk 15 tim PKM Udayana yang lolos ke tahap final PKM Nasional 2024. Semoga sukses di presentasi!',
                        'days_ago' => 12
                    ]
                ]
            ],
            [
                'name' => 'Lowongan Kerja',
                'description' => 'Informasi lowongan kerja, magang, dan peluang karir untuk mahasiswa dan alumni.',
                'creator_id' => $adminUser->id,
                'moderator_data' => [
                    'name' => 'Koordinator Karir',
                    'username' => 'koord_karir',
                    'email' => 'koordinator.karir@student.unud.ac.id',
                    'prodi' => 'Manajemen',
                    'fakultas' => 'Ekonomi dan Bisnis',
                ],
                'is_approved' => true,
                'sample_posts' => [
                    [
                        'content' => '💼 LOWONGAN: PT. Tokopedia membuka posisi Software Engineer Intern untuk mahasiswa semester 6+. Apply sekarang!',
                        'days_ago' => 1
                    ],
                    [
                        'content' => '🏠 Remote job opportunity: Content Creator untuk startup edtech internasional. Cocok untuk mahasiswa kreatif.',
                        'days_ago' => 3
                    ],
                    [
                        'content' => '🎯 Job Fair Udayana 2025 akan diselenggarakan bulan Maret. 50+ perusahaan akan hadir untuk recruitment.',
                        'days_ago' => 7
                    ]
                ]
            ]
        ];

        // ✅ ENHANCED: Loop untuk membuat komunitas dengan moderator system
        foreach ($communities as $communityData) {
            $existingCommunity = ChatGroup::where('name', $communityData['name'])->first();
            
            if (!$existingCommunity) {
                // ✅ ENHANCED: Buat atau ambil moderator
                $moderator = User::where('email', $communityData['moderator_data']['email'])->first();
                
                if (!$moderator) {
                    $moderator = User::create([
                        'full_name' => $communityData['moderator_data']['name'],
                        'username' => $communityData['moderator_data']['username'],
                        'email' => $communityData['moderator_data']['email'],
                        'password' => bcrypt('password'),
                        'role' => 'mahasiswa', // Moderator adalah mahasiswa biasa dengan akses khusus
                        'is_verified' => true,
                        'prodi' => $communityData['moderator_data']['prodi'],
                        'fakultas' => $communityData['moderator_data']['fakultas'],
                        'gender' => rand(0, 1) ? 'Laki-laki' : 'Perempuan',
                        'description' => "Koordinator untuk komunitas {$communityData['name']}",
                        'interests' => ['coordination', 'community_management', 'leadership'],
                        'match_categories' => ['committee', 'jobs', 'friends']
                    ]);
                    
                    echo "✅ Created moderator: {$communityData['moderator_data']['name']}\n";
                }

                // ✅ ENHANCED: Buat komunitas dengan moderator
                $community = ChatGroup::create([
                    'name' => $communityData['name'],
                    'description' => $communityData['description'],
                    'creator_id' => $communityData['creator_id'],
                    'moderator_id' => $moderator->id,
                    'is_approved' => $communityData['is_approved'],
                ]);

                echo "✅ Created community: {$communityData['name']} (Moderator: {$moderator->full_name})\n";

                // ✅ ENHANCED: Tambahkan sample posts dengan variasi waktu
                foreach ($communityData['sample_posts'] as $postData) {
                    $createdAt = now()->subDays($postData['days_ago'])->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                    
                    GroupMessage::create([
                        'group_id' => $community->id,
                        'sender_id' => rand(0, 1) ? $moderator->id : $adminUser->id, // Random antara moderator dan admin
                        'message_content' => $postData['content'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                echo "   📝 Added " . count($communityData['sample_posts']) . " sample posts\n";

                // ✅ NEW: Add sample reactions dan comments untuk beberapa posts
                $this->addSampleInteractions($community, $moderator, $adminUser);

            } else {
                echo "ℹ️  Community '{$communityData['name']}' already exists, skipping...\n";
            }
        }

        echo "\n🎉 Enhanced Community seeding completed!\n";
        echo "📊 Summary:\n";
        echo "   🏘️  Total communities: " . ChatGroup::where('is_approved', true)->count() . "\n";
        echo "   👥 Total moderators: " . ChatGroup::whereNotNull('moderator_id')->distinct('moderator_id')->count() . "\n";
        echo "   💬 Total messages: " . GroupMessage::count() . "\n";
        
        echo "\n📋 Community-Moderator Mapping:\n";
        $communities = ChatGroup::with(['moderator:id,full_name'])->get();
        foreach ($communities as $community) {
            if ($community->moderator) {
                echo "   🏷️  {$community->name} → {$community->moderator->full_name}\n";
            } else {
                echo "   🏷️  {$community->name} → No moderator assigned\n";
            }
        }
    }

    /**
     * ✅ NEW: Add sample reactions dan comments untuk testing
     */
    private function addSampleInteractions($community, $moderator, $admin)
    {
        $messages = $community->messages()->latest()->take(2)->get();
        
        foreach ($messages as $message) {
            // Add sample reactions
            if (rand(0, 1)) {
                \App\Models\PostReaction::create([
                    'message_id' => $message->id,
                    'user_id' => $moderator->id,
                    'reaction_type' => ['like', 'heart', 'thumbs_up'][rand(0, 2)],
                ]);
            }
            
            if (rand(0, 1)) {
                \App\Models\PostReaction::create([
                    'message_id' => $message->id,
                    'user_id' => $admin->id,
                    'reaction_type' => ['like', 'celebrate'][rand(0, 1)],
                ]);
            }
            
            // Add sample comments
            if (rand(0, 1)) {
                \App\Models\PostComment::create([
                    'message_id' => $message->id,
                    'user_id' => $moderator->id,
                    'comment_content' => 'Informasi yang sangat bermanfaat! Terima kasih telah berbagi.',
                ]);
            }
        }
    }
}