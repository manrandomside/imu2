<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubmissionCategory;

class SubmissionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Lomba & Kompetisi',
                'slug' => 'lomba-kompetisi',
                'description' => 'Submit informasi lomba dan kompetisi untuk mahasiswa. Termasuk lomba programming, design, business plan, dan kompetisi akademik lainnya.',
                'price' => 5000.00,
                'is_active' => true,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760, // 10MB
            ],
            [
                'name' => 'Lowongan Kerja',
                'slug' => 'lowongan-kerja',
                'description' => 'Submit lowongan kerja full-time, part-time, magang, dan freelance. Untuk perusahaan yang ingin merekrut mahasiswa dan alumni.',
                'price' => 5000.00,
                'is_active' => true,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760, // 10MB
            ],
            [
                'name' => 'Seminar & Workshop',
                'slug' => 'seminar-workshop',
                'description' => 'Submit informasi seminar, workshop, webinar, dan acara edukatif. Untuk event yang bersifat pembelajaran dan pengembangan skill.',
                'price' => 5000.00,
                'is_active' => true,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760, // 10MB
            ],
            [
                'name' => 'Event & Acara',
                'slug' => 'event-acara',
                'description' => 'Submit informasi event kampus, acara mahasiswa, festival, dan kegiatan sosial. Untuk semua jenis acara non-akademik.',
                'price' => 5000.00,
                'is_active' => true,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760, // 10MB
            ],
            [
                'name' => 'Beasiswa & Grant',
                'slug' => 'beasiswa-grant',
                'description' => 'Submit informasi beasiswa dalam negeri, luar negeri, research grant, dan bantuan dana pendidikan.',
                'price' => 5000.00,
                'is_active' => true,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760, // 10MB
            ],
            [
                'name' => 'Pelatihan & Kursus',
                'slug' => 'pelatihan-kursus',
                'description' => 'Submit informasi pelatihan profesional, kursus online, bootcamp, dan program sertifikasi.',
                'price' => 5000.00,
                'is_active' => true,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760, // 10MB
            ],
        ];

        foreach ($categories as $category) {
            SubmissionCategory::updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Submission categories seeded successfully!');
    }
}