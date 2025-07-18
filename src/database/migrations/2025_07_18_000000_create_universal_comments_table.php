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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('評論者');
            $table->morphs('commentable', 'comments_commentable_index'); // 多型態關聯
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade')->comment('父評論ID（用於回覆）');
            $table->text('content')->comment('評論內容');
            $table->integer('rating')->nullable()->comment('評分（1-5星）');
            $table->enum('status', ['published', 'pending', 'rejected', 'deleted'])->default('published')->comment('評論狀態');
            $table->integer('likes_count')->default(0)->comment('點讚數');
            $table->integer('replies_count')->default(0)->comment('回覆數');
            $table->boolean('is_pinned')->default(false)->comment('是否置頂');
            $table->json('metadata')->nullable()->comment('額外資訊（如標籤、分類等）');
            $table->timestamps();
            $table->softDeletes(); // 軟刪除
            
            // 索引
            $table->index(['commentable_type', 'commentable_id']);
            $table->index(['parent_id']);
            $table->index(['status']);
            $table->index(['created_at']);
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
            
            // 確保同一用戶對同一評論只能點讚一次
            $table->unique(['comment_id', 'member_id']);
        });

        Schema::create('comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->onDelete('cascade');
            $table->foreignId('reporter_id')->constrained('members')->onDelete('cascade');
            $table->enum('reason', ['spam', 'inappropriate', 'harassment', 'misinformation', 'other'])->comment('檢舉原因');
            $table->text('description')->nullable()->comment('詳細描述');
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending')->comment('處理狀態');
            $table->timestamps();
        });

        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('type', ['like', 'love', 'laugh', 'angry', 'sad', 'wow'])->comment('反應類型');
            $table->timestamps();
            
            // 確保同一用戶對同一評論只能有一種反應
            $table->unique(['comment_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
        Schema::dropIfExists('comment_reports');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('comments');
    }
};