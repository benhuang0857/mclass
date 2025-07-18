<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomMeetDetail extends Model
{
    use HasFactory;

    protected $table = 'zoom_meet_detail';

    protected $fillable = [
        'club_course_id',
        'zoom_meeting_id',
        'zoom_meeting_uuid',
        'host_id',
        'topic',
        'type',
        'start_time',
        'duration',
        'timezone',
        'agenda',
        'password',
        'h323_password',
        'pstn_password',
        'encrypted_password',
        'join_url',
        'start_url',
        'link',
        'settings',
        'status',
        'zoom_created_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'zoom_created_at' => 'datetime',
        'settings' => 'array',
        'type' => 'integer',
        'duration' => 'integer',
    ];

    /**
     * 關聯到課程
     */
    public function clubCourse(): BelongsTo
    {
        return $this->belongsTo(ClubCourse::class, 'club_course_id');
    }

    /**
     * 檢查會議是否為即時會議
     */
    public function isInstantMeeting(): bool
    {
        return $this->type === 1;
    }

    /**
     * 檢查會議是否為預定會議
     */
    public function isScheduledMeeting(): bool
    {
        return $this->type === 2;
    }

    /**
     * 檢查會議是否為定期會議
     */
    public function isRecurringMeeting(): bool
    {
        return in_array($this->type, [3, 8]);
    }

    /**
     * 檢查會議是否為試聽課程
     */
    public function isTrialCourse(): bool
    {
        return $this->clubCourse->trial ?? false;
    }

    /**
     * 獲取會議類型描述
     */
    public function getTypeDescriptionAttribute(): string
    {
        return match($this->type) {
            1 => '即時會議',
            2 => '預定會議',
            3 => '定期會議（無固定時間）',
            8 => '定期會議（固定時間）',
            default => '未知類型'
        };
    }

    /**
     * 檢查會議是否可以加入
     */
    public function canJoinNow(): bool
    {
        $now = now();
        $meetingStart = $this->start_time;
        $meetingEnd = $this->start_time->addMinutes($this->duration);
        
        // 提前30分鐘開放，延後30分鐘關閉
        return $now->between(
            $meetingStart->subMinutes(30),
            $meetingEnd->addMinutes(30)
        );
    }

    /**
     * 獲取會議加入時間範圍
     */
    public function getJoinTimeRange(): array
    {
        $meetingStart = $this->start_time;
        $meetingEnd = $this->start_time->addMinutes($this->duration);
        
        return [
            'available_from' => $meetingStart->subMinutes(30),
            'available_until' => $meetingEnd->addMinutes(30),
            'meeting_start' => $meetingStart,
            'meeting_end' => $meetingEnd,
        ];
    }

    /**
     * 查詢範圍：活躍的會議
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 查詢範圍：今日的會議
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    /**
     * 查詢範圍：即將開始的會議
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now());
    }

    /**
     * 查詢範圍：進行中的會議
     */
    public function scopeOngoing($query)
    {
        $now = now();
        return $query->where('start_time', '<=', $now)
                    ->whereRaw('DATE_ADD(start_time, INTERVAL duration MINUTE) >= ?', [$now]);
    }
}