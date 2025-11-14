<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'flip_course_case_id',
        'counselor_id',
        'counseling_appointment_id',
        'cycle_number',
        'status',
        'issued_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => 'string',
        'cycle_number' => 'integer',
        'issued_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 所屬案例
     */
    public function flipCourseCase(): BelongsTo
    {
        return $this->belongsTo(FlipCourseCase::class);
    }

    /**
     * 開立處方的諮商師
     */
    public function counselor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'counselor_id');
    }

    /**
     * 關聯的諮商會議
     */
    public function counselingAppointment(): BelongsTo
    {
        return $this->belongsTo(CounselingAppointment::class);
    }

    /**
     * 派發的課程 (多對多)
     */
    public function clubCourses(): BelongsToMany
    {
        return $this->belongsToMany(ClubCourseInfo::class, 'prescription_club_courses')
            ->withPivot('reason', 'recommended_sessions')
            ->withTimestamps();
    }

    /**
     * 學習任務
     */
    public function learningTasks(): HasMany
    {
        return $this->hasMany(LearningTask::class);
    }

    /**
     * 分析結果
     */
    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }

    /**
     * 處方項目 (關聯的處方內容)
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('sort_order');
    }

    /**
     * 檢查是否已開立
     */
    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    /**
     * 檢查是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 計算任務完成率
     */
    public function getTaskCompletionRate(): float
    {
        $total = $this->learningTasks()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->learningTasks()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }
}
