<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'notification_type',
        'enabled',
        'delivery_methods',
        'advance_minutes',
        'schedule_settings',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'delivery_methods' => 'array',
        'schedule_settings' => 'array',
        'advance_minutes' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * 檢查是否啟用特定推送方式
     */
    public function hasDeliveryMethod($method)
    {
        return in_array($method, $this->delivery_methods ?? []);
    }

    /**
     * 檢查是否在靜音時間內
     */
    public function isInQuietHours($dateTime = null)
    {
        if (!$this->schedule_settings || !isset($this->schedule_settings['quiet_hours'])) {
            return false;
        }

        $dateTime = $dateTime ?: now();
        $currentTime = $dateTime->format('H:i');
        $quietHours = $this->schedule_settings['quiet_hours'];

        if (!isset($quietHours['start']) || !isset($quietHours['end'])) {
            return false;
        }

        $startTime = $quietHours['start'];
        $endTime = $quietHours['end'];

        // 處理跨日的情況（如：22:00 到 08:00）
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        } else {
            return $currentTime >= $startTime && $currentTime <= $endTime;
        }
    }

    /**
     * 獲取有效的提前提醒時間
     */
    public function getEffectiveAdvanceMinutes($defaultMinutes = 60)
    {
        return $this->advance_minutes ?? $defaultMinutes;
    }

    /**
     * 創建用戶的預設通知偏好
     */
    public static function createDefaultPreferences($memberId)
    {
        $defaultPreferences = [
            // 課程相關
            ['notification_type' => 'course_reminder', 'advance_minutes' => 60],
            ['notification_type' => 'course_change', 'advance_minutes' => null],
            ['notification_type' => 'course_new_class', 'advance_minutes' => null],
            ['notification_type' => 'course_price_change', 'enabled' => false], // 預設關閉
            ['notification_type' => 'course_status_change', 'advance_minutes' => null],
            ['notification_type' => 'course_registration_deadline', 'advance_minutes' => null],
            
            // 諮商相關
            ['notification_type' => 'counseling_reminder', 'advance_minutes' => 60],
            ['notification_type' => 'counseling_confirmed', 'advance_minutes' => null],
            ['notification_type' => 'counseling_status_change', 'advance_minutes' => null],
            ['notification_type' => 'counseling_time_change', 'advance_minutes' => null],
            ['notification_type' => 'counselor_new_service', 'enabled' => false], // 預設關閉
            
            // 總開關
            ['notification_type' => 'all', 'advance_minutes' => null],
        ];

        foreach ($defaultPreferences as $preference) {
            self::create(array_merge([
                'member_id' => $memberId,
                'enabled' => $preference['enabled'] ?? true,
                'delivery_methods' => ['database'],
                'schedule_settings' => null,
            ], $preference));
        }
    }
}