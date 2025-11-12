<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'title',
        'description',
        'resources',
        'estimated_hours',
        'status',
        'progress',
        'due_date',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => 'string',
        'progress' => 'integer',
        'estimated_hours' => 'integer',
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 所屬處方簽
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * 檢查是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 檢查是否逾期
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isCompleted();
    }

    /**
     * 標記為開始
     */
    public function markAsStarted(): void
    {
        if ($this->status === 'pending') {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }
    }

    /**
     * 標記為完成
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
        ]);
    }
}
