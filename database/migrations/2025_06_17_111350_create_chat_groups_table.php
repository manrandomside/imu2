    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('chat_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // Nama grup/saluran, unik
                $table->text('description')->nullable(); // Deskripsi grup
                $table->foreignId('creator_id')->constrained('users')->onDelete('cascade'); // Siapa yang mengajukan/membuat grup
                $table->boolean('is_approved')->default(false); // Status persetujuan admin
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('chat_groups');
        }
    };
    