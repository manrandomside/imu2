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
            Schema::create('chat_group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('chat_groups')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('role')->default('member'); // Misal: 'member', 'admin_group'
                $table->timestamp('joined_at')->useCurrent(); // Waktu bergabung
                $table->timestamps();

                // Pastikan satu user hanya bisa jadi member satu kali di satu grup
                $table->unique(['group_id', 'user_id']);
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('chat_group_members');
        }
    };
    