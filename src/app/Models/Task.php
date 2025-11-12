<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'flip_course_case_id',
        'assignee_id',
        'taskable_type',
        'taskable_id',
        'type',
        'status',
        'priority',
        'title',
        'description',
        'metadata',
        'due_date',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'type' => 'string',
        'status' => 'string',
        'priority' => 'string',
        'due_date' => 'datetime',
        'started_at' => 'datetime',
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
     * 負責人
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assignee_id');
    }

    /**
     * 關聯的業務實體 (多態)
     */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 依賴的任務
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withTimestamps();
    }

    /**
     * 被依賴的任務（被哪些任務依賴）
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withTimestamps();
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
     * 檢查是否被阻擋
     */
    public function isBlocked(): bool
    {
        if ($this->status === 'blocked') {
            return true;
        }

        // 檢查依賴的任務是否都已完成
        return $this->dependencies()->where('status', '!=', 'completed')->exists();
    }

    /**
     * 標記為開始
     */
    public function markAsStarted(): void
    {
        if ($this->status === 'pending' && !$this->isBlocked()) {
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
            'completed_at' => now(),
        ]);

        // 檢查依賴此任務的其他任務是否可以解除阻擋
        $this->unblockDependentTasks();
    }

    /**
     * 解除被阻擋的依賴任務
     */
    protected function unblockDependentTasks(): void
    {
        $this->dependents()
            ->where('status', 'blocked')
            ->each(function ($task) {
                if (!$task->isBlocked()) {
                    $task->update(['status' => 'pending']);
                }
            });
    }

    /**
     * 取消任務
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
