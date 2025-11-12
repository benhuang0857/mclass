<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'type',
        'related_type',
        'related_id',
        'title',
        'content',
        'data',
        'is_read',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopePending($query)
    {
        return $query->whereNull('sent_at')
                    ->where('scheduled_at', '<=', now());
    }

    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function markAsSent()
    {
        $this->update(['sent_at' => now()]);
    }

    /**
     * 範圍查詢：翻轉課程相關通知
     */
    public function scopeFlipCourseRelated($query)
    {
        return $query->whereIn('type', [
            'flip_case_assigned',
            'flip_task_assigned',
            'flip_prescription_issued',
            'flip_analysis_completed',
            'flip_cycle_started',
            'flip_case_completed',
        ]);
    }

    /**
     * 範圍查詢：課程相關通知
     */
    public function scopeCourseRelated($query)
    {
        return $query->where('type', 'like', 'course_%');
    }

    /**
     * 範圍查詢：諮商相關通知
     */
    public function scopeCounselingRelated($query)
    {
        return $query->where('type', 'like', 'counseling_%')
                    ->orWhere('type', 'like', 'counselor_%');
    }

    /**
     * 檢查是否為翻轉課程通知
     */
    public function isFlipCourseNotification(): bool
    {
        return str_starts_with($this->type, 'flip_');
    }
}