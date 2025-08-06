<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'club_course_id',
        'member_id',
        'status',
        'check_in_time',
        'check_out_time',
        'note',
        'marked_by',
        'marked_at',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'marked_at' => 'datetime',
    ];

    // 出席狀態常數
    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_EARLY_LEAVE = 'early_leave';
    const STATUS_EXCUSED = 'excused';

    /**
     * 取得所有可用的出席狀態
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PRESENT => '出席',
            self::STATUS_ABSENT => '缺席',
            self::STATUS_LATE => '遲到',
            self::STATUS_EARLY_LEAVE => '早退',
            self::STATUS_EXCUSED => '請假',
        ];
    }

    /**
     * 關聯到課程
     */
    public function clubCourse(): BelongsTo
    {
        return $this->belongsTo(ClubCourse::class, 'club_course_id');
    }

    /**
     * 關聯到學生
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 關聯到點名者
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * 獲取出席狀態的中文描述
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getAvailableStatuses()[$this->status] ?? '未知';
    }

    /**
     * 檢查是否遲到
     */
    public function isLate(): bool
    {
        if (!$this->check_in_time || !$this->clubCourse) {
            return false;
        }

        return $this->check_in_time->gt($this->clubCourse->start_time);
    }

    /**
     * 檢查是否早退
     */
    public function isEarlyLeave(): bool
    {
        if (!$this->check_out_time || !$this->clubCourse) {
            return false;
        }

        return $this->check_out_time->lt($this->clubCourse->end_time);
    }

    /**
     * 計算遲到時間（分鐘）
     */
    public function getLateMinutesAttribute(): int
    {
        if (!$this->isLate()) {
            return 0;
        }

        return $this->check_in_time->diffInMinutes($this->clubCourse->start_time);
    }

    /**
     * 計算早退時間（分鐘）
     */
    public function getEarlyLeaveMinutesAttribute(): int
    {
        if (!$this->isEarlyLeave()) {
            return 0;
        }

        return $this->clubCourse->end_time->diffInMinutes($this->check_out_time);
    }

    /**
     * 計算實際上課時間（分鐘）
     */
    public function getActualDurationAttribute(): ?int
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }

    /**
     * 查詢範圍：特定課程的出席記錄
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('club_course_id', $courseId);
    }

    /**
     * 查詢範圍：特定學生的出席記錄
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查詢範圍：特定狀態的記錄
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 查詢範圍：出席的記錄（包含遲到和早退）
     */
    public function scopeAttended($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PRESENT,
            self::STATUS_LATE,
            self::STATUS_EARLY_LEAVE
        ]);
    }

    /**
     * 查詢範圍：缺席的記錄
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    /**
     * 查詢範圍：請假的記錄
     */
    public function scopeExcused($query)
    {
        return $query->where('status', self::STATUS_EXCUSED);
    }

    /**
     * 查詢範圍：按日期範圍篩選
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereHas('clubCourse', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_time', [$startDate, $endDate]);
        });
    }

    /**
     * 自動設定點名時間和狀態判斷
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendance) {
            if (!$attendance->marked_at) {
                $attendance->marked_at = now();
            }

            // 自動判斷遲到狀態
            if ($attendance->status === self::STATUS_PRESENT && $attendance->check_in_time) {
                $course = ClubCourse::find($attendance->club_course_id);
                if ($course && $attendance->check_in_time->gt($course->start_time)) {
                    $attendance->status = self::STATUS_LATE;
                }
            }
        });

        static::updating(function ($attendance) {
            // 更新時重新判斷遲到狀態
            if ($attendance->status === self::STATUS_PRESENT && $attendance->check_in_time) {
                $course = $attendance->clubCourse;
                if ($course && $attendance->check_in_time->gt($course->start_time)) {
                    $attendance->status = self::STATUS_LATE;
                }
            }
        });
    }
}
