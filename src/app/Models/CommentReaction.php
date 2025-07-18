<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'member_id',
        'type',
    ];

    /**
     * 評論
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * 反應者
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 反應類型的描述
     */
    public function getTypeDescriptionAttribute(): string
    {
        return match($this->type) {
            'like' => '讚',
            'love' => '愛心',
            'laugh' => '哈哈',
            'angry' => '憤怒',
            'sad' => '難過',
            'wow' => '哇',
            default => '未知'
        };
    }
}