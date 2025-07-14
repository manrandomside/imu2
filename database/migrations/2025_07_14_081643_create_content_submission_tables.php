<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create submission_categories table first
        Schema::create('submission_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(5000.00);
            $table->boolean('is_active')->default(true);
            $table->json('allowed_file_types')->nullable(); // ['jpg', 'png', 'pdf', 'doc']
            $table->integer('max_file_size')->default(10485760); // 10MB in bytes
            $table->timestamps();
        });

        // Insert default categories
        $categories = [
            [
                'name' => 'Lomba & Kompetisi',
                'slug' => 'lomba-kompetisi',
                'description' => 'Submit informasi lomba dan kompetisi untuk mahasiswa. Termasuk lomba programming, design, business plan, dan kompetisi akademik lainnya.',
                'price' => 5000.00,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760
            ],
            [
                'name' => 'Lowongan Kerja',
                'slug' => 'lowongan-kerja',
                'description' => 'Submit lowongan kerja full-time, part-time, magang, dan freelance. Untuk perusahaan yang ingin merekrut mahasiswa dan alumni.',
                'price' => 5000.00,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760
            ],
            [
                'name' => 'Seminar & Workshop',
                'slug' => 'seminar-workshop',
                'description' => 'Submit informasi seminar, workshop, webinar, dan acara edukatif. Untuk event yang bersifat pembelajaran dan pengembangan skill.',
                'price' => 5000.00,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760
            ],
            [
                'name' => 'Event & Acara',
                'slug' => 'event-acara',
                'description' => 'Submit informasi event kampus, acara mahasiswa, festival, dan kegiatan sosial. Untuk semua jenis acara non-akademik.',
                'price' => 5000.00,
                'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']),
                'max_file_size' => 10485760
            ]
        ];

        foreach ($categories as $category) {
            DB::table('submission_categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        // Create payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['transfer_bank', 'dana', 'gopay', 'ovo'])->default('transfer_bank');
            $table->string('payment_proof_path')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('payment_details')->nullable(); // For storing additional payment info
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Create content_submissions table - FIX: category_id should reference submission_categories
        Schema::create('content_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('submission_categories')->onDelete('cascade'); // FIX: changed from chat_groups
            $table->string('title');
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_type')->nullable();
            $table->string('attachment_name')->nullable();
            $table->integer('attachment_size')->nullable();
            $table->enum('status', ['pending_payment', 'pending_approval', 'approved', 'rejected', 'published'])->default('pending_payment');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['category_id', 'status']);
        });

        // Update payments table to reference content_submissions
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('submission_id')->nullable()->constrained('content_submissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_submissions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('submission_categories');
    }
};