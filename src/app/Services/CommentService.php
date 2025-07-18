<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\CommentReaction;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use DB;

class CommentService
{
    /**
     * 獲取實體的評論列表
     */
    public function getComments(Model $commentable, array $options = []): LengthAwarePaginator
    {
        $query = $commentable->comments()
            ->published()
            ->with(['author.profile', 'replies.author.profile']);

        // 應用篩選器
        if (isset($options['sort'])) {
            match($options['sort']) {
                'newest' => $query->byTime('desc'),
                'oldest' => $query->byTime('asc'),
                'popular' => $query->byLikes(),
                'rating_high' => $query->orderBy('rating', 'desc'),
                'rating_low' => $query->orderBy('rating', 'asc'),
                default => $query->byTime('desc')
            };
        }

        // 篩選有評分的評論
        if (isset($options['has_rating']) && $options['has_rating']) {
            $query->whereNotNull('rating');
        }

        // 篩選特定評分
        if (isset($options['rating'])) {
            $query->where('rating', $options['rating']);
        }

        // 只顯示根評論
        if (isset($options['root_only']) && $options['root_only']) {
            $query->rootComments();
        }

        // 置頂評論優先
        if (isset($options['pinned_first']) && $options['pinned_first']) {
            $query->orderBy('is_pinned', 'desc');
        }

        return $query->paginate($options['per_page'] ?? 15);
    }

    /**
     * 創建評論
     */
    public function createComment(Model $commentable, Member $author, array $data): Comment
    {
        return DB::transaction(function () use ($commentable, $author, $data) {
            $comment = $commentable->addComment([
                'member_id' => $author->id,
                'content' => $data['content'],
                'rating' => $data['rating'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'status' => $data['status'] ?? 'published',
            ]);

            // 如果是回覆，更新父評論的回覆數
            if ($comment->parent_id) {
                $comment->parent->updateRepliesCount();
            }

            return $comment->load(['author.profile']);
        });
    }

    /**
     * 更新評論
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        $comment->update([
            'content' => $data['content'] ?? $comment->content,
            'rating' => $data['rating'] ?? $comment->rating,
            'metadata' => $data['metadata'] ?? $comment->metadata,
        ]);

        return $comment->load(['author.profile']);
    }

    /**
     * 刪除評論
     */
    public function deleteComment(Comment $comment): bool
    {
        return DB::transaction(function () use ($comment) {
            // 軟刪除評論
            $deleted = $comment->delete();

            // 更新父評論的回覆數
            if ($comment->parent_id) {
                $comment->parent->updateRepliesCount();
            }

            return $deleted;
        });
    }

    /**
     * 切換評論點讚狀態
     */
    public function toggleLike(Comment $comment, Member $member): array
    {
        return DB::transaction(function () use ($comment, $member) {
            $isLiked = $comment->isLikedBy($member);

            if ($isLiked) {
                // 取消點讚
                $comment->likedByUsers()->detach($member->id);
                $action = 'unliked';
            } else {
                // 點讚
                $comment->likedByUsers()->attach($member->id);
                $action = 'liked';
            }

            // 更新點讚數
            $comment->updateLikesCount();

            return [
                'action' => $action,
                'likes_count' => $comment->likes_count,
                'is_liked' => !$isLiked,
            ];
        });
    }

    /**
     * 添加反應
     */
    public function addReaction(Comment $comment, Member $member, string $type): CommentReaction
    {
        return DB::transaction(function () use ($comment, $member, $type) {
            // 移除舊的反應
            $comment->reactions()->where('member_id', $member->id)->delete();

            // 添加新的反應
            return $comment->reactions()->create([
                'member_id' => $member->id,
                'type' => $type,
            ]);
        });
    }

    /**
     * 移除反應
     */
    public function removeReaction(Comment $comment, Member $member): bool
    {
        return $comment->reactions()
                      ->where('member_id', $member->id)
                      ->delete() > 0;
    }

    /**
     * 檢舉評論
     */
    public function reportComment(Comment $comment, Member $reporter, array $data): CommentReport
    {
        return $comment->reports()->create([
            'reporter_id' => $reporter->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * 置頂/取消置頂評論
     */
    public function togglePin(Comment $comment): Comment
    {
        $comment->update(['is_pinned' => !$comment->is_pinned]);
        return $comment;
    }

    /**
     * 批量操作評論狀態
     */
    public function batchUpdateStatus(array $commentIds, string $status): int
    {
        return Comment::whereIn('id', $commentIds)
                     ->update(['status' => $status]);
    }

    /**
     * 獲取評論統計
     */
    public function getCommentStatistics(Model $commentable): array
    {
        $stats = $commentable->getCommentStats();
        
        // 添加額外統計 - 使用獨立的查詢
        $recentComments = $commentable->morphMany(Comment::class, 'commentable')
                                     ->published()
                                     ->with(['author.profile'])
                                     ->orderBy('created_at', 'desc')
                                     ->limit(5)
                                     ->get();
        
        $popularComments = $commentable->morphMany(Comment::class, 'commentable')
                                      ->published()
                                      ->with(['author.profile'])
                                      ->orderBy('likes_count', 'desc')
                                      ->limit(5)
                                      ->get();
        
        $stats['recent_comments'] = $recentComments;
        $stats['popular_comments'] = $popularComments;
        
        return $stats;
    }

    /**
     * 搜尋評論
     */
    public function searchComments(string $query, array $options = []): LengthAwarePaginator
    {
        $search = Comment::query()
            ->published()
            ->with(['author.profile', 'commentable']);

        // 搜尋內容
        if (!empty($query)) {
            $search->where('content', 'LIKE', "%{$query}%");
        }

        // 篩選評論類型
        if (isset($options['commentable_type'])) {
            $search->where('commentable_type', $options['commentable_type']);
        }

        // 篩選評分
        if (isset($options['rating'])) {
            $search->where('rating', $options['rating']);
        }

        // 篩選日期範圍
        if (isset($options['date_from'])) {
            $search->where('created_at', '>=', $options['date_from']);
        }

        if (isset($options['date_to'])) {
            $search->where('created_at', '<=', $options['date_to']);
        }

        // 排序
        $sort = $options['sort'] ?? 'newest';
        match($sort) {
            'newest' => $search->byTime('desc'),
            'oldest' => $search->byTime('asc'),
            'popular' => $search->byLikes(),
            default => $search->byTime('desc')
        };

        return $search->paginate($options['per_page'] ?? 15);
    }

    /**
     * 獲取用戶的評論歷史
     */
    public function getUserComments(Member $member, array $options = []): LengthAwarePaginator
    {
        $query = Comment::where('member_id', $member->id)
                        ->published()
                        ->with(['commentable', 'replies.author.profile']);

        // 篩選評論類型
        if (isset($options['commentable_type'])) {
            $query->where('commentable_type', $options['commentable_type']);
        }

        // 排序
        $sort = $options['sort'] ?? 'newest';
        match($sort) {
            'newest' => $query->byTime('desc'),
            'oldest' => $query->byTime('asc'),
            'popular' => $query->byLikes(),
            default => $query->byTime('desc')
        };

        return $query->paginate($options['per_page'] ?? 15);
    }

    /**
     * 獲取熱門評論
     */
    public function getTrendingComments(int $limit = 10): Collection
    {
        return Comment::published()
                     ->rootComments()
                     ->with(['author.profile', 'commentable'])
                     ->where('created_at', '>=', now()->subDays(7))
                     ->byLikes()
                     ->limit($limit)
                     ->get();
    }
}