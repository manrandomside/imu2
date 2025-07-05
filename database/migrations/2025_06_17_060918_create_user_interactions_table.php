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
            Schema::create('user_interactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pengguna yang melakukan swipe
                $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade'); // Pengguna yang di-swipe
                $table->enum('action_type', ['like', 'dislike']); // Tipe aksi: 'like' atau 'dislike'
                $table->timestamps();

                // Tambahkan unique constraint untuk mencegah duplikasi like/dislike dari user yang sama ke target yang sama
                $table->unique(['user_id', 'target_user_id']);
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('user_interactions');
        }
    };
    