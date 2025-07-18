<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'reporter_id',
        'reason',
        'description',
        'status',
    ];

    /**
     * 被檢舉的評論
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * 檢舉者
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reporter_id');
    }

    /**
     * 查詢範圍：待處理的檢舉
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 查詢範圍：已處理的檢舉
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}