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
            Schema::create('matches', function (Blueprint $table) {
                $table->id();
                // user1_id selalu lebih kecil dari user2_id untuk memastikan uniqueness dari pasangan match
                $table->foreignId('user1_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('user2_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                // Pastikan kombinasi user1_id dan user2_id adalah unik
                $table->unique(['user1_id', 'user2_id']);
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('matches');
        }
    };
    