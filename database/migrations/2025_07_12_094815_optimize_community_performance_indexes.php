<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ✅ Migration untuk optimasi performance community features
     * Jalankan: php artisan make:migration optimize_community_performance_indexes
     */
    public function up(): void
    {
        // 1. Add indexes untuk better performance pada group_messages
        Schema::table('group_messages', function (Blueprint $table) {
            // Index untuk query messages by group dengan sorting
            $table->index(['group_id', 'created_at']);
            
            // Index untuk query messages by sender
            $table->index(['sender_id', 'created_at']);
            
            // Index untuk attachment queries
            $table->index('attachment_type');
        });

        // 2. Add indexes untuk post_reactions
        Schema::table('post_reactions', function (Blueprint $table) {
            // Index untuk query reactions by message
            $table->index(['message_id', 'reaction_type']);
            
            // Index untuk user reactions (prevent duplicates)
            $table->index(['user_id', 'message_id']);
            
            // Index untuk counting reactions
            $table->index(['message_id', 'created_at']);
        });

        // 3. Add indexes untuk post_comments  
        Schema::table('post_comments', function (Blueprint $table) {
            // Index untuk query comments by message
            $table->index(['message_id', 'parent_id', 'created_at']);
            
            // Index untuk nested comments
            $table->index(['parent_id', 'created_at']);
            
            // Index untuk user comments
            $table->index(['user_id', 'created_at']);
        });

        // 4. Add indexes untuk chat_groups optimization
        Schema::table('chat_groups', function (Blueprint $table) {
            // Index untuk approved groups query
            $table->index(['is_approved', 'name']);
            
            // Index untuk moderator queries
            $table->index('moderator_id');
        });

        // 5. ✅ OPTIONAL: Add full-text search index untuk message content
        // Uncomment jika ingin fitur search (MySQL 5.7+)
        /*
        DB::statement('ALTER TABLE group_messages ADD FULLTEXT(message_content)');
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->dropIndex(['group_id', 'created_at']);
            $table->dropIndex(['sender_id', 'created_at']);
            $table->dropIndex(['attachment_type']);
        });

        Schema::table('post_reactions', function (Blueprint $table) {
            $table->dropIndex(['message_id', 'reaction_type']);
            $table->dropIndex(['user_id', 'message_id']);
            $table->dropIndex(['message_id', 'created_at']);
        });

        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropIndex(['message_id', 'parent_id', 'created_at']);
            $table->dropIndex(['parent_id', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('chat_groups', function (Blueprint $table) {
            $table->dropIndex(['is_approved', 'name']);
            $table->dropIndex(['moderator_id']);
        });

        // Drop full-text index jika ada
        /*
        DB::statement('ALTER TABLE group_messages DROP INDEX message_content');
        */
    }
};