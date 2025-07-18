<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'commentable_type',
        'commentable_id',
        'parent_id',
        'content',
        'rating',
        'status',
        'likes_count',
        'replies_count',
        'is_pinned',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'likes_count' => 'integer',
        'replies_count' => 'integer',
        'is_pinned' => 'boolean',
        'metadata' => 'array',
    ];

    protected $with = ['author'];

    /**
     * 評論者
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 被評論的實體（多型態關聯）
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 父評論
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * 子評論（回覆）
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
                    ->with(['author', 'replies'])
                    ->orderBy('created_at', 'asc');
    }

    /**
     * 所有子評論（遞迴）
     */
    public function allReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
                    ->with(['author', 'allReplies'])
                    ->orderBy('created_at', 'asc');
    }

    /**
     * 點讚的用戶
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'comment_likes', 'comment_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * 檢舉記錄
     */
    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /**
     * 反應記錄
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    /**
     * 檢查用戶是否已點讚
     */
    public function isLikedBy(Member $member): bool
    {
        return $this->likedByUsers()->where('member_id', $member->id)->exists();
    }

    /**
     * 檢查是否為根評論
     */
    public function isRootComment(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 檢查是否為回覆
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * 獲取評論深度
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        
        return $depth;
    }

    /**
     * 獲取根評論
     */
    public function getRootComment(): Comment
    {
        if ($this->isRootComment()) {
            return $this;
        }
        
        $parent = $this->parent;
        while ($parent && $parent->parent) {
            $parent = $parent->parent;
        }
        
        return $parent ?: $this;
    }

    /**
     * 更新回覆數量
     */
    public function updateRepliesCount(): void
    {
        $this->replies_count = $this->replies()->count();
        $this->save();
    }

    /**
     * 更新點讚數量
     */
    public function updateLikesCount(): void
    {
        $this->likes_count = $this->likedByUsers()->count();
        $this->save();
    }

    /**
     * 軟刪除時更新父評論的回覆數
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($comment) {
            if ($comment->parent_id) {
                $comment->parent->updateRepliesCount();
            }
        });

        static::deleted(function ($comment) {
            if ($comment->parent_id) {
                $comment->parent->updateRepliesCount();
            }
        });
    }

    /**
     * 查詢範圍：已發布的評論
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * 查詢範圍：根評論
     */
    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 查詢範圍：按點讚數排序
     */
    public function scopeByLikes($query)
    {
        return $query->orderBy('likes_count', 'desc');
    }

    /**
     * 查詢範圍：按時間排序
     */
    public function scopeByTime($query, $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * 查詢範圍：最新評論
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 查詢範圍：置頂評論
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}