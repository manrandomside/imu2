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
        // 1. Add moderator_id to chat_groups table
        Schema::table('chat_groups', function (Blueprint $table) {
            $table->foreignId('moderator_id')->nullable()->after('creator_id')->constrained('users')->onDelete('set null');
        });

        // 2. Add file support to group_messages table
        Schema::table('group_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('message_content');
            $table->string('attachment_type')->nullable()->after('attachment_path'); // image, document, etc
            $table->string('attachment_name')->nullable()->after('attachment_type');
            $table->integer('attachment_size')->nullable()->after('attachment_name'); // in bytes
        });

        // 3. Create reactions table
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('group_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('reaction_type', ['like', 'heart', 'thumbs_up', 'celebrate'])->default('like');
            $table->timestamps();

            // Prevent duplicate reactions
            $table->unique(['message_id', 'user_id']);
        });

        // 4. Create comments table
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('group_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('comment_content');
            $table->foreignId('parent_id')->nullable()->constrained('post_comments')->onDelete('cascade'); // For nested replies
            $table->timestamps();
        });

        // 5. Create push notification subscriptions table
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('endpoint');
            $table->text('public_key')->nullable();
            $table->text('auth_token')->nullable();
            $table->text('content_encoding')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'endpoint']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('post_reactions');
        
        Schema::table('group_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_type', 'attachment_name', 'attachment_size']);
        });
        
        Schema::table('chat_groups', function (Blueprint $table) {
            $table->dropForeign(['moderator_id']);
            $table->dropColumn('moderator_id');
        });
    }
};