<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChatGroup;
use App\Models\GroupMessage;
use Illuminate\Support\Facades\Hash;

class CommunitySeeder extends Seeder
{
    /**
     * ✅ COMPLETE: Seed communities dengan moderator dan sample content
     */
    public function run()
    {
        $this->command->info('🚀 Starting Community Seeder...');

        // ✅ STEP 1: Create Admin User (if not exists)
        $admin = User::firstOrCreate(
            ['email' => 'admin@unud.ac.id'],
            [
                'full_name' => 'Administrator IMU',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_verified' => true,
                'prodi' => 'Sistem Informasi',
                'fakultas' => 'Teknik',
                'gender' => 'Laki-laki',
                'description' => 'Administrator sistem IMU untuk mengelola komunitas kampus',
                'interests' => ['management', 'technology'],
                'match_categories' => ['admin']
            ]
        );

        $this->command->info("✅ Admin created: {$admin->full_name} (ID: {$admin->id})");

        // ✅ STEP 2: Create Moderator Users
        $moderators = [
            [
                'name' => 'Moderator PKM',
                'email' => 'mod.pkm@unud.ac.id',
                'username' => 'mod_pkm',
                'prodi' => 'Teknik Informatika',
                'description' => 'Moderator khusus untuk mengelola informasi PKM dan kompetisi mahasiswa'
            ],
            [
                'name' => 'Moderator Beasiswa',
                'email' => 'mod.beasiswa@unud.ac.id', 
                'username' => 'mod_beasiswa',
                'prodi' => 'Manajemen',
                'description' => 'Moderator untuk mengelola informasi beasiswa dalam dan luar negeri'
            ],
            [
                'name' => 'Moderator Karir',
                'email' => 'mod.karir@unud.ac.id',
                'username' => 'mod_karir', 
                'prodi' => 'Ekonomi',
                'description' => 'Moderator untuk mengelola lowongan kerja dan informasi karir'
            ],
            [
                'name' => 'Moderator Event',
                'email' => 'mod.event@unud.ac.id',
                'username' => 'mod_event',
                'prodi' => 'Komunikasi',
                'description' => 'Moderator untuk mengelola event, workshop, dan seminar'
            ]
        ];

        $createdModerators = [];
        foreach ($moderators as $modData) {
            $moderator = User::firstOrCreate(
                ['email' => $modData['email']],
                [
                    'full_name' => $modData['name'],
                    'username' => $modData['username'],
                    'password' => Hash::make('moderator123'),
                    'role' => 'moderator',
                    'is_verified' => true,
                    'prodi' => $modData['prodi'],
                    'fakultas' => 'Teknik',
                    'gender' => 'Laki-laki',
                    'description' => $modData['description'],
                    'interests' => ['education', 'community'],
                    'match_categories' => ['moderator']
                ]
            );
            
            $createdModerators[$modData['username']] = $moderator;
            $this->command->info("✅ Moderator created: {$moderator->full_name} (ID: {$moderator->id})");
        }

        // ✅ STEP 3: Create Communities dengan Moderator Assignment
        $communities = [
            [
                'name' => 'Pengumuman Umum',
                'description' => 'Saluran resmi untuk pengumuman penting dari universitas dan fakultas. Hanya admin yang dapat memposting di sini.',
                'moderator' => null, // Admin only
                'sample_posts' => [
                    'Selamat datang di sistem komunitas IMU! 🎉\n\nSistem ini dirancang untuk memfasilitasi komunikasi antar mahasiswa, alumni, dan dosen di Universitas Udayana. Mari kita manfaatkan platform ini dengan baik untuk berbagi informasi dan saling membantu.\n\nSalam,\nTim IMU'
                ]
            ],
            [
                'name' => 'PKM & Kompetisi',
                'description' => 'Informasi lengkap tentang Program Kreativitas Mahasiswa (PKM), lomba, kompetisi akademik dan non-akademik tingkat nasional maupun internasional.',
                'moderator' => 'mod_pkm',
                'sample_posts' => [
                    '🏆 PENGUMUMAN PKM 2025 🏆\n\nPendaftaran PKM (Program Kreativitas Mahasiswa) tahun 2025 telah dibuka!\n\n📅 Timeline:\n• Batas akhir pendaftaran: 31 Maret 2025\n• Periode pelaksanaan: April - Oktober 2025\n• Presentasi final: November 2025\n\n💰 Dana tersedia:\n• PKM-PE: Rp 12.500.000\n• PKM-KC: Rp 10.000.000\n• PKM-GT: Rp 8.000.000\n\nInfo lengkap: bit.ly/pkm2025-unud\n\n#PKM2025 #KreatifitasMahasiswa',
                    '🎯 LOMBA KARYA TULIS ILMIAH NASIONAL\n\nHalo teman-teman! Ada lomba menarik nih:\n\n📚 Lomba: Karya Tulis Ilmiah Nasional\n🏛️ Penyelenggara: Universitas Indonesia\n💰 Hadiah total: Rp 50.000.000\n📅 Deadline: 15 Februari 2025\n\n📋 Tema: "Inovasi Teknologi untuk Pembangunan Berkelanjutan"\n\nYuk yang minat, langsung daftar! Link pendaftaran ada di bio.\n\n#LombaKTI #MahasiswaBerprestasi'
                ]
            ],
            [
                'name' => 'Info Beasiswa',
                'description' => 'Informasi beasiswa dalam negeri dan luar negeri, termasuk tips aplikasi, deadline, dan pengalaman dari penerima beasiswa.',
                'moderator' => 'mod_beasiswa',
                'sample_posts' => [
                    '🎓 BEASISWA LPDP 2025 DIBUKA! 🎓\n\nKabar gembira untuk teman-teman yang ingin melanjutkan studi S2/S3!\n\n📚 Program:\n• S2 Dalam Negeri\n• S2 Luar Negeri  \n• S3 Dalam Negeri\n• S3 Luar Negeri\n\n💡 Tips aplikasi:\n✅ Siapkan proposal penelitian yang kuat\n✅ Pelajari visi-misi LPDP\n✅ Latihan interview dari sekarang\n✅ Tingkatkan kemampuan bahasa\n\n📅 Pendaftaran: 15 Januari - 15 Maret 2025\n\nInfo lengkap: www.lpdp.kemenkeu.go.id\n\n#BeasiswaLPDP #StudiLanjut',
                    '🌟 TIPS SUKSES MENDAPAT BEASISWA\n\nBerdasarkan pengalaman alumni yang berhasil:\n\n1️⃣ Mulai persiapan minimal 6 bulan sebelumnya\n2️⃣ Fokus pada IPK dan prestasi akademik\n3️⃣ Aktif di organisasi dan kegiatan sosial\n4️⃣ Tingkatkan kemampuan bahasa Inggris\n5️⃣ Buat CV yang menarik dan professional\n6️⃣ Siapkan motivation letter yang compelling\n7️⃣ Cari referensi dari dosen atau supervisor\n\n💪 Jangan menyerah, terus berusaha!\n\n#TipsBeasiswa #MotivationMonday'
                ]
            ],
            [
                'name' => 'Lowongan Kerja',
                'description' => 'Informasi lowongan kerja, magang, part-time jobs, dan peluang karir untuk mahasiswa dan alumni.',
                'moderator' => 'mod_karir',
                'sample_posts' => [
                    '💼 LOWONGAN KERJA TERBARU!\n\n🏢 PT. Techno Indonesia\n📍 Posisi: Software Developer\n📍 Lokasi: Jakarta/Remote\n💰 Gaji: Rp 8-15 juta\n\n📋 Requirements:\n• S1 Teknik Informatika/sejenisnya\n• Pengalaman minimal 1 tahun\n• Menguasai Laravel, React, MySQL\n• Fresh graduate welcome\n\n📧 Kirim CV ke: career@technoindonesia.com\n📅 Deadline: 28 Februari 2025\n\nGood luck! 🍀\n\n#LokerIT #SoftwareDeveloper #Jakarta',
                    '🚀 PROGRAM MAGANG BANK MANDIRI\n\n📅 Periode: Maret - Agustus 2025\n📍 Cabang: Denpasar, Ubud, Singaraja\n💰 Tunjangan: Rp 2.5 juta/bulan\n\n🎯 Untuk mahasiswa:\n• Semester 6-8\n• IPK min 3.25\n• Aktif organisasi\n• Komunikasi baik\n\n📝 Benefits:\n✅ Certificate completion\n✅ Networking dengan professionals\n✅ Kemungkinan full-time hiring\n✅ Training leadership\n\nDaftar online: karir.bankmandiri.co.id\n\n#MagangBankMandiri #OpportunitiesBali'
                ]
            ],
            [
                'name' => 'Event & Workshop',
                'description' => 'Informasi seminar, workshop, training, webinar, dan acara-acara yang bermanfaat untuk pengembangan diri.',
                'moderator' => 'mod_event',
                'sample_posts' => [
                    '🎪 WORKSHOP: "Digital Marketing for Gen Z"\n\n📅 Tanggal: Sabtu, 25 Januari 2025\n⏰ Waktu: 09.00 - 16.00 WITA\n📍 Tempat: Aula Fakultas Teknik UNUD\n💰 HTM: Rp 75.000 (mahasiswa Unud: Rp 50.000)\n\n👨‍🏫 Pembicara:\n• Budi Santoso (Digital Marketing Manager Tokopedia)\n• Sarah Lestari (Content Creator 1M followers)\n\n📚 Materi:\n✅ Social Media Strategy\n✅ Content Creation Tips\n✅ Analytics & Monitoring\n✅ Personal Branding\n\n🎁 Bonus: Certificate + E-book + Template\n\nDaftar: bit.ly/workshop-digitalmarketing\n\n#WorkshopMarketing #SkillDevelopment',
                    '🎓 SEMINAR NASIONAL: "AI & Future Jobs"\n\n📢 Universitas Udayana mengundang mahasiswa se-Indonesia!\n\n📅 Tanggal: 15 Februari 2025\n⏰ Waktu: 08.00 - 17.00 WITA\n📍 Tempat: Auditorium Agrokompleks UNUD\n💰 GRATIS dengan registrasi\n\n🌟 Keynote Speaker:\n• Prof. Dr. Bambang Riyanto (ITB)\n• Dr. Andi Susilo (Senior Data Scientist Google)\n• Ria Wulandari (AI Researcher MIT)\n\n📋 Agenda:\n• Future of AI Technology\n• Impact on Job Market\n• Skills for Digital Era\n• Panel Discussion\n\nRegistrasi: seminarnasional.unud.ac.id\n\n#SeminarAI #FutureJobs #UNUD'
                ]
            ],
            [
                'name' => 'Info Akademik',
                'description' => 'Informasi akademik seperti jadwal kuliah, perubahan kurikulum, periode registrasi, dan pengumuman resmi fakultas.',
                'moderator' => null, // Admin only untuk info resmi
                'sample_posts' => [
                    '📚 PENGUMUMAN JADWAL UAS SEMESTER GENAP 2024/2025\n\n📅 Periode UAS: 2-16 Juni 2025\n📋 Jadwal detail per fakultas telah diunggah di SIMAK NG\n\n⚠️ PENTING:\n✅ Pastikan tidak ada tunggakan UKT\n✅ Cek jadwal di SIMAK NG setiap hari\n✅ Bawa KTM dan identitas saat ujian\n✅ Datang minimal 15 menit sebelum ujian\n\n📞 Info lebih lanjut:\n• BAAK: (0361) 701954\n• Email: baak@unud.ac.id\n\nSelamat mempersiapkan ujian! 💪\n\n#UAS #SemesterGenap #UNUD',
                    '🎯 PERIODE REGISTRASI SEMESTER GANJIL 2025/2026\n\n📅 Jadwal Registrasi:\n• Gelombang 1: 15-30 Juli 2025\n• Gelombang 2: 1-15 Agustus 2025\n\n💰 Pembayaran UKT:\n• Via Bank Mandiri\n• Via SIMAK NG (Virtual Account)\n• Deadline: sesuai gelombang\n\n📋 Dokumen yang diperlukan:\n✅ Bukti pembayaran UKT\n✅ KRS yang telah disetujui PA\n✅ Kartu mahasiswa aktif\n\n⚠️ Mahasiswa yang terlambat registrasi akan dikenakan denda!\n\nInfo: registrasi.unud.ac.id\n\n#RegistrasiMahasiswa #UKT #SIMAKNG'
                ]
            ]
        ];

        // ✅ STEP 4: Create Communities and Sample Messages
        foreach ($communities as $communityData) {
            // Create community
            $community = ChatGroup::firstOrCreate(
                ['name' => $communityData['name']],
                [
                    'description' => $communityData['description'],
                    'creator_id' => $admin->id,
                    'moderator_id' => $communityData['moderator'] ? $createdModerators[$communityData['moderator']]->id : null,
                    'is_approved' => true,
                ]
            );

            $this->command->info("✅ Community created: {$community->name} (ID: {$community->id})");

            // Add sample messages
            if (isset($communityData['sample_posts'])) {
                foreach ($communityData['sample_posts'] as $index => $postContent) {
                    $sender = $community->moderator_id ? $createdModerators[$communityData['moderator']] : $admin;
                    
                    GroupMessage::firstOrCreate(
                        [
                            'group_id' => $community->id,
                            'sender_id' => $sender->id,
                            'message_content' => $postContent
                        ],
                        [
                            'created_at' => now()->subDays(rand(1, 7))->subHours(rand(1, 23)),
                            'updated_at' => now()->subDays(rand(1, 7))->subHours(rand(1, 23)),
                        ]
                    );
                }
                
                $postCount = count($communityData['sample_posts']);
                $this->command->info("   📝 Added {$postCount} sample posts");
            }
        }

        // ✅ STEP 5: Create Regular Test Users (optional)
        $testUsers = [
            [
                'name' => 'Made Agus Mahasiswa',
                'email' => 'agus@student.unud.ac.id',
                'username' => 'agus_student',
                'role' => 'mahasiswa',
                'prodi' => 'Teknik Informatika'
            ],
            [
                'name' => 'Ni Luh Alumni',
                'email' => 'luh@alumni.unud.ac.id', 
                'username' => 'luh_alumni',
                'role' => 'alumni',
                'prodi' => 'Sistem Informasi'
            ]
        ];

        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'full_name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => Hash::make('password123'),
                    'role' => $userData['role'],
                    'is_verified' => true,
                    'prodi' => $userData['prodi'],
                    'fakultas' => 'Teknik',
                    'gender' => 'Laki-laki',
                    'description' => "Test user - {$userData['role']}",
                    'interests' => ['technology', 'education'],
                    'match_categories' => ['friends', 'jobs']
                ]
            );
            
            $this->command->info("✅ Test user created: {$user->full_name} ({$user->role})");
        }

        // ✅ STEP 6: Summary
        $this->command->info('');
        $this->command->info('🎉 COMMUNITY SEEDER COMPLETED!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('• Communities: ' . ChatGroup::count());
        $this->command->info('• Admin users: ' . User::where('role', 'admin')->count());
        $this->command->info('• Moderator users: ' . User::where('role', 'moderator')->count());
        $this->command->info('• Sample messages: ' . GroupMessage::count());
        $this->command->info('');
        $this->command->info('🔐 Login credentials:');
        $this->command->info('• Admin: admin@unud.ac.id / admin123');
        $this->command->info('• Moderator PKM: mod.pkm@unud.ac.id / moderator123');
        $this->command->info('• Moderator Beasiswa: mod.beasiswa@unud.ac.id / moderator123');
        $this->command->info('• Test Mahasiswa: agus@student.unud.ac.id / password123');
        $this->command->info('');
        $this->command->info('✅ Silakan login dan test community features!');
    }
}