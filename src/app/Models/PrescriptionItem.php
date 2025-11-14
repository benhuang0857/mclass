<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'item_type',
        'title',
        'description',
        'metadata',
        'sort_order',
        'status',
        'completion_notes',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'item_type' => 'string',
        'status' => 'string',
        'sort_order' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * 項目類型常數
     */
    const TYPE_TASK = 'task';           // 學習任務
    const TYPE_COURSE = 'course';       // 課程建議
    const TYPE_RESOURCE = 'resource';   // 學習資源
    const TYPE_ASSESSMENT = 'assessment'; // 測驗/評量
    const TYPE_NOTE = 'note';           // 備註說明
    const TYPE_GOAL = 'goal';           // 學習目標
    const TYPE_OTHER = 'other';         // 其他

    /**
     * 狀態常數
     */
    const STATUS_PENDING = 'pending';       // 待處理
    const STATUS_ACTIVE = 'active';         // 進行中
    const STATUS_COMPLETED = 'completed';   // 已完成
    const STATUS_CANCELLED = 'cancelled';   // 已取消

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
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * 檢查是否進行中
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 標記為已完成
     */
    public function markAsCompleted(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completion_notes' => $notes,
            'completed_at' => now(),
        ]);
    }

    /**
     * 取得 metadata 中的特定值
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * 設定 metadata 中的特定值
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Scope: 依項目類型篩選
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope: 依狀態篩選
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: 只取得進行中的項目
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: 只取得已完成的項目
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: 依排序順序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
