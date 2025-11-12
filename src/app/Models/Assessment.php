<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'analyst_id',
        'test_content',
        'test_results',
        'test_score',
        'analysis_report',
        'metrics',
        'recommendations',
        'study_hours',
        'tasks_completed',
        'courses_attended',
        'status',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'test_results' => 'array',
        'metrics' => 'array',
        'recommendations' => 'array',
        'test_score' => 'integer',
        'study_hours' => 'integer',
        'tasks_completed' => 'integer',
        'courses_attended' => 'integer',
        'status' => 'string',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 對應的處方簽
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * 負責的分析師
     */
    public function analyst(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'analyst_id');
    }

    /**
     * 檢查是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 檢查是否在審查中
     */
    public function isInReview(): bool
    {
        return $this->status === 'in_review';
    }

    /**
     * 提交分析
     */
    public function submit(): void
    {
        $this->update([
            'status' => 'in_review',
            'submitted_at' => now(),
        ]);
    }

    /**
     * 標記為完成
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
