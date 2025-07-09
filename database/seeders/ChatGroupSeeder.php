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
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada user admin atau creator untuk komunitas
        $adminUser = User::where('role', 'admin')->first();
        
        // Jika tidak ada admin, buat user admin dummy atau gunakan user pertama
        if (!$adminUser) {
            $adminUser = User::first();
            if (!$adminUser) {
                // Jika tidak ada user sama sekali, buat admin dummy
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

        // ✅ REVISI: Data komunitas baru sesuai request dengan moderator system
        $communities = [
            [
                'name' => 'Lomba',
                'description' => 'Informasi kompetisi, lomba, dan kontes untuk mahasiswa Universitas Udayana.',
                'creator_id' => $adminUser->id,
                'moderator_name' => 'Moderator Lomba',
                'moderator_username' => 'mod_lomba',
                'moderator_email' => 'moderator.lomba@student.unud.ac.id',
                'is_approved' => true,
                'icon_placeholder' => '🏆',
                'sample_posts' => [
                    'KOMPETISI NASIONAL: Lomba Karya Tulis Ilmiah tingkat nasional dengan total hadiah 50 juta rupiah. Deadline: 15 Februari 2025.',
                    'Hackathon "Innovation for Indonesia" akan diselenggarakan bulan depan. Daftarkan tim terbaikmu sekarang!',
                    'Selamat untuk tim Debat Udayana yang meraih juara 1 di Kompetisi Debat Nasional 2024! 🎉'
                ]
            ],
            [
                'name' => 'Workshop',
                'description' => 'Workshop, pelatihan, dan kegiatan pengembangan skill untuk mahasiswa.',
                'creator_id' => $adminUser->id,
                'moderator_name' => 'Moderator Workshop',
                'moderator_username' => 'mod_workshop',
                'moderator_email' => 'moderator.workshop@student.unud.ac.id',
                'is_approved' => true,
                'icon_placeholder' => '🛠️',
                'sample_posts' => [
                    'WORKSHOP: "Digital Marketing for Beginners" - 20 Januari 2025, Gedung Rektorat Lt.2. Gratis untuk 50 peserta pertama!',
                    'Pelatihan Microsoft Excel Advanced akan diadakan minggu depan. Cocok untuk mahasiswa yang ingin improve data analysis skills.',
                    'Workshop "Public Speaking & Presentation Skills" berhasil diselenggarakan dengan 100+ peserta antusias!'
                ]
            ],
            [
                'name' => 'Seminar',
                'description' => 'Seminar nasional, webinar, dan talk show dengan pembicara expert.',
                'creator_id' => $adminUser->id,
                'moderator_name' => 'Moderator Seminar',
                'moderator_username' => 'mod_seminar',
                'moderator_email' => 'moderator.seminar@student.unud.ac.id',
                'is_approved' => true,
                'icon_placeholder' => '🎤',
                'sample_posts' => [
                    'SEMINAR NASIONAL: "Future of Artificial Intelligence in Indonesia" - 25 Januari 2025. Pembicara: CEO startup AI terkemuka.',
                    'Webinar "Career Preparation for Fresh Graduate" bersama HRD dari berbagai perusahaan multinasional.',
                    'Talk Show: "Entrepreneurship Journey" dengan founder-founder startup unicorn Indonesia. Don\'t miss it!'
                ]
            ],
            [
                'name' => 'Info Beasiswa',
                'description' => 'Informasi beasiswa dalam dan luar negeri untuk mahasiswa Udayana.',
                'creator_id' => $adminUser->id,
                'moderator_name' => 'Moderator Beasiswa',
                'moderator_username' => 'mod_beasiswa',
                'moderator_email' => 'moderator.beasiswa@student.unud.ac.id',
                'is_approved' => true,
                'icon_placeholder' => '🎓',
                'sample_posts' => [
                    'BEASISWA LPDP 2025: Pendaftaran dibuka untuk program S2/S3 dalam dan luar negeri. Persiapkan dokumen dari sekarang!',
                    'Beasiswa penuh ke Jerman untuk jurusan Engineering dan Computer Science. Deadline: 31 Maret 2025.',
                    'Info Beasiswa internal Udayana semester genap 2025 sudah dirilis. Cek syarat dan ketentuan di website resmi.'
                ]
            ],
            [
                'name' => 'PKM',
                'description' => 'Program Kreativitas Mahasiswa, riset, dan kegiatan akademik inovatif.',
                'creator_id' => $adminUser->id,
                'moderator_name' => 'Moderator PKM',
                'moderator_username' => 'mod_pkm',
                'moderator_email' => 'moderator.pkm@student.unud.ac.id',
                'is_approved' => true,
                'icon_placeholder' => '🔬',
                'sample_posts' => [
                    'DEADLINE PKM 2025 diperpanjang hingga 20 Februari! Tim yang belum submit segera finalisasi proposal.',
                    'Workshop "How to Write Winning PKM Proposal" akan diadakan untuk membantu mahasiswa menyusun proposal yang berkualitas.',
                    'Selamat untuk 15 tim PKM Udayana yang lolos ke tahap final PKM Nasional 2024. Semoga sukses di presentasi!'
                ]
            ],
            [
                'name' => 'Lowongan Kerja',
                'description' => 'Informasi lowongan kerja, magang, dan peluang karir untuk mahasiswa dan alumni.',
                'creator_id' => $adminUser->id,
                'moderator_name' => 'Moderator Karir',
                'moderator_username' => 'mod_karir',
                'moderator_email' => 'moderator.karir@student.unud.ac.id',
                'is_approved' => true,
                'icon_placeholder' => '💼',
                'sample_posts' => [
                    'LOWONGAN: PT. Tokopedia membuka posisi Software Engineer Intern untuk mahasiswa semester 6+. Apply sekarang!',
                    'Remote job opportunity: Content Creator untuk startup edtech internasional. Cocok untuk mahasiswa kreatif.',
                    'Job Fair Udayana 2025 akan diselenggarakan bulan Maret. 50+ perusahaan akan hadir untuk recruitment.'
                ]
            ]
        ];

        // ✅ REVISI: Loop untuk membuat komunitas, moderator, dan pesan sample
        foreach ($communities as $communityData) {
            // Cek apakah komunitas sudah ada
            $existingCommunity = ChatGroup::where('name', $communityData['name'])->first();
            
            if (!$existingCommunity) {
                // ✅ BARU: Buat moderator untuk komunitas ini
                $moderator = User::where('email', $communityData['moderator_email'])->first();
                
                if (!$moderator) {
                    $moderator = User::create([
                        'full_name' => $communityData['moderator_name'],
                        'username' => $communityData['moderator_username'],
                        'email' => $communityData['moderator_email'],
                        'password' => bcrypt('password'),
                        'role' => 'mahasiswa', // ✅ Moderator masih regular user, bukan admin
                        'is_verified' => true,
                        'prodi' => 'Teknik Informatika',
                        'fakultas' => 'Teknik',
                        'gender' => rand(0, 1) ? 'Laki-laki' : 'Perempuan',
                        'description' => "Moderator untuk komunitas {$communityData['name']}",
                        'interests' => ['moderation', 'community_management'],
                        'match_categories' => ['committee', 'jobs']
                    ]);
                    
                    echo "✅ Created moderator: {$communityData['moderator_name']}\n";
                }

                // ✅ REVISI: Buat komunitas baru dengan moderator
                $community = ChatGroup::create([
                    'name' => $communityData['name'],
                    'description' => $communityData['description'],
                    'creator_id' => $communityData['creator_id'],
                    'moderator_id' => $moderator->id, // ✅ BARU: Assign moderator
                    'is_approved' => $communityData['is_approved'],
                ]);

                echo "✅ Created community: {$communityData['name']} (Moderator: {$moderator->full_name})\n";

                // ✅ REVISI: Tambahkan beberapa pesan sample dari moderator
                foreach ($communityData['sample_posts'] as $index => $postContent) {
                    GroupMessage::create([
                        'group_id' => $community->id,
                        'sender_id' => $moderator->id, // ✅ BARU: Posts dari moderator, bukan admin
                        'message_content' => $postContent,
                        'created_at' => now()->subDays(rand(1, 30)),
                        'updated_at' => now()->subDays(rand(1, 30)),
                    ]);
                }

                echo "   📝 Added " . count($communityData['sample_posts']) . " sample posts\n";
            } else {
                echo "ℹ️  Community '{$communityData['name']}' already exists, skipping...\n";
            }
        }

        echo "\n🎉 Enhanced Chat Group seeding completed!\n";
        echo "📊 Total communities: " . ChatGroup::where('is_approved', true)->count() . "\n";
        echo "👥 Total moderators: " . ChatGroup::whereNotNull('moderator_id')->count() . "\n";
        echo "💬 Total messages: " . GroupMessage::count() . "\n";
        
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
}