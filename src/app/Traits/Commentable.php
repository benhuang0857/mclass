<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Commentable
{
    /**
     * 獲取所有評論（不包含排序，避免 GROUP BY 衝突）
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * 獲取已發布的評論
     */
    public function publishedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
                    ->published()
                    ->with(['author', 'replies.author'])
                    ->orderBy('created_at', 'desc');
    }

    /**
     * 獲取根評論（不包括回覆）
     */
    public function rootComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
                    ->rootComments()
                    ->published()
                    ->with(['author', 'replies.author'])
                    ->orderBy('created_at', 'desc');
    }

    /**
     * 獲取置頂評論
     */
    public function pinnedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
                    ->pinned()
                    ->published()
                    ->with(['author', 'replies.author'])
                    ->orderBy('created_at', 'desc');
    }

    /**
     * 獲取評論統計
     */
    public function getCommentStats(): array
    {
        // 使用獨立的查詢來避免 GROUP BY 衝突
        $baseQuery = $this->comments()->published();
        
        // 評分分布查詢 - 使用全新的查詢實例
        $ratingDistribution = $this->morphMany(Comment::class, 'commentable')
                                  ->published()
                                  ->whereNotNull('rating')
                                  ->selectRaw('rating, COUNT(*) as count')
                                  ->groupBy('rating')
                                  ->orderBy('rating', 'asc')
                                  ->pluck('count', 'rating')
                                  ->toArray();
        
        return [
            'total_comments' => $baseQuery->count(),
            'total_replies' => $baseQuery->whereNotNull('parent_id')->count(),
            'total_root_comments' => $baseQuery->whereNull('parent_id')->count(),
            'average_rating' => $baseQuery->whereNotNull('rating')->avg('rating'),
            'rating_distribution' => $ratingDistribution,
        ];
    }

    /**
     * 檢查用戶是否已評論
     */
    public function hasCommentedBy($memberId): bool
    {
        return $this->comments()
                    ->where('member_id', $memberId)
                    ->exists();
    }

    /**
     * 獲取用戶的評論
     */
    public function getCommentBy($memberId): ?Comment
    {
        return $this->comments()
                    ->where('member_id', $memberId)
                    ->first();
    }

    /**
     * 添加評論
     */
    public function addComment(array $data): Comment
    {
        return $this->comments()->create($data);
    }

    /**
     * 獲取最新評論
     */
    public function latestComments(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->comments()
                    ->published()
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * 獲取熱門評論（按點讚數排序）
     */
    public function popularComments(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->comments()
                    ->published()
                    ->orderBy('likes_count', 'desc')
                    ->limit($limit)
                    ->get();
    }
}