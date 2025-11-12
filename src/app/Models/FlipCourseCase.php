<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlipCourseCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'flip_course_info_id',
        'student_id',
        'order_id',
        'planner_id',
        'counselor_id',
        'analyst_id',
        'workflow_stage',
        'cycle_count',
        'line_group_url',
        'payment_status',
        'payment_confirmed_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'workflow_stage' => 'string',
        'payment_status' => 'string',
        'cycle_count' => 'integer',
        'payment_confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 翻轉課程模板
     */
    public function flipCourseInfo(): BelongsTo
    {
        return $this->belongsTo(FlipCourseInfo::class);
    }

    /**
     * 學生
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'student_id');
    }

    /**
     * 訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 規劃師
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'planner_id');
    }

    /**
     * 諮商師
     */
    public function counselor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'counselor_id');
    }

    /**
     * 分析師
     */
    public function analyst(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'analyst_id');
    }

    /**
     * 處方簽
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * 最新的處方簽
     */
    public function latestPrescription()
    {
        return $this->hasOne(Prescription::class)->latestOfMany();
    }

    /**
     * 任務
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * 待處理的任務
     */
    public function pendingTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('status', 'pending');
    }

    /**
     * 備註/日誌
     */
    public function notes(): HasMany
    {
        return $this->hasMany(FlipCourseCaseNote::class);
    }

    /**
     * 檢查是否在循環階段
     */
    public function isInCycle(): bool
    {
        return $this->workflow_stage === 'cycling';
    }

    /**
     * 檢查是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->workflow_stage === 'completed';
    }

    /**
     * 檢查金流是否已確認
     */
    public function isPaymentConfirmed(): bool
    {
        return $this->payment_status === 'confirmed';
    }
}
