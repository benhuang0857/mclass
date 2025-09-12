<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\Member;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * 獲取用戶的通知偏好設定
     */
    public function index(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $preferences = NotificationPreference::forMember($request->member_id)
            ->orderBy('notification_type')
            ->get();

        // 如果用戶還沒有偏好設定，創建預設設定
        if ($preferences->isEmpty()) {
            NotificationPreference::createDefaultPreferences($request->member_id);
            $preferences = NotificationPreference::forMember($request->member_id)
                ->orderBy('notification_type')
                ->get();
        }

        return response()->json([
            'preferences' => $preferences,
            'available_delivery_methods' => [
                'database' => '系統通知',
                'email' => 'Email',
                'push' => '推播通知',
                'sms' => '簡訊'
            ],
            'available_notification_types' => [
                // 課程相關
                'course_reminder' => '課程開始提醒',
                'course_change' => '課程變更通知',
                'course_new_class' => '課程新班開設',
                'course_price_change' => '課程價格變動',
                'course_status_change' => '課程狀態變更',
                'course_registration_deadline' => '報名截止提醒',
                
                // 諮商相關
                'counseling_reminder' => '諮商開始提醒',
                'counseling_confirmed' => '諮商預約確認',
                'counseling_status_change' => '諮商狀態變更',
                'counseling_time_change' => '諮商時間變更',
                'counselor_new_service' => '諮商師新服務',
                
                // 總開關
                'all' => '所有通知總開關',
            ]
        ]);
    }

    /**
     * 更新單一通知偏好設定
     */
    public function update(Request $request, $id)
    {
        $preference = NotificationPreference::findOrFail($id);

        $validated = $request->validate([
            'enabled' => 'boolean',
            'delivery_methods' => 'array',
            'delivery_methods.*' => 'in:database,email,push,sms',
            'advance_minutes' => 'nullable|integer|min:1|max:10080', // 最多7天
            'schedule_settings' => 'nullable|array',
            'schedule_settings.quiet_hours' => 'nullable|array',
            'schedule_settings.quiet_hours.start' => 'nullable|date_format:H:i',
            'schedule_settings.quiet_hours.end' => 'nullable|date_format:H:i',
        ]);

        $preference->update($validated);

        return response()->json([
            'message' => 'Notification preference updated successfully',
            'preference' => $preference->fresh(),
        ]);
    }

    /**
     * 批量更新通知偏好設定
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.enabled' => 'boolean',
            'preferences.*.delivery_methods' => 'array',
            'preferences.*.delivery_methods.*' => 'in:database,email,push,sms',
            'preferences.*.advance_minutes' => 'nullable|integer|min:1|max:10080',
            'preferences.*.schedule_settings' => 'nullable|array',
        ]);

        $updatedCount = 0;
        $errors = [];

        foreach ($request->preferences as $preferenceData) {
            try {
                $preference = NotificationPreference::forMember($request->member_id)
                    ->forType($preferenceData['notification_type'])
                    ->first();

                if (!$preference) {
                    // 如果不存在，創建新的偏好設定
                    $preference = NotificationPreference::create(array_merge(
                        $preferenceData,
                        ['member_id' => $request->member_id]
                    ));
                } else {
                    // 更新現有的偏好設定
                    $preference->update($preferenceData);
                }

                $updatedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'notification_type' => $preferenceData['notification_type'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => "Successfully updated {$updatedCount} preferences",
            'updated_count' => $updatedCount,
            'errors' => $errors,
        ]);
    }

    /**
     * 重設為預設偏好設定
     */
    public function resetToDefaults(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        // 刪除現有偏好設定
        NotificationPreference::forMember($request->member_id)->delete();

        // 創建預設偏好設定
        NotificationPreference::createDefaultPreferences($request->member_id);

        $newPreferences = NotificationPreference::forMember($request->member_id)
            ->orderBy('notification_type')
            ->get();

        return response()->json([
            'message' => 'Notification preferences reset to defaults',
            'preferences' => $newPreferences,
        ]);
    }

    /**
     * 快速設定（全部啟用/停用）
     */
    public function quickSetting(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'action' => 'required|in:enable_all,disable_all,enable_important_only',
        ]);

        $memberId = $request->member_id;

        switch ($request->action) {
            case 'enable_all':
                NotificationPreference::forMember($memberId)
                    ->update(['enabled' => true]);
                $message = 'All notifications enabled';
                break;

            case 'disable_all':
                NotificationPreference::forMember($memberId)
                    ->update(['enabled' => false]);
                $message = 'All notifications disabled';
                break;

            case 'enable_important_only':
                // 先全部關閉
                NotificationPreference::forMember($memberId)
                    ->update(['enabled' => false]);
                
                // 只開啟重要通知
                $importantTypes = [
                    'course_reminder',
                    'course_change',
                    'counseling_reminder',
                    'counseling_confirmed',
                    'counseling_time_change',
                ];
                
                NotificationPreference::forMember($memberId)
                    ->whereIn('notification_type', $importantTypes)
                    ->update(['enabled' => true]);
                
                $message = 'Only important notifications enabled';
                break;
        }

        $preferences = NotificationPreference::forMember($memberId)
            ->orderBy('notification_type')
            ->get();

        return response()->json([
            'message' => $message,
            'preferences' => $preferences,
        ]);
    }

    /**
     * 測試通知設定
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'notification_type' => 'required|string',
        ]);

        $preference = NotificationPreference::forMember($request->member_id)
            ->forType($request->notification_type)
            ->first();

        if (!$preference || !$preference->enabled) {
            return response()->json([
                'message' => 'This notification type is disabled',
                'would_send' => false,
            ]);
        }

        // 創建測試通知
        $testNotification = [
            'member_id' => $request->member_id,
            'type' => $request->notification_type,
            'related_type' => 'test',
            'related_id' => 0,
            'title' => '測試通知',
            'content' => '這是一個測試通知，用來驗證您的通知設定是否正常工作。',
            'data' => [
                'is_test' => true,
                'sent_at' => now()->toISOString(),
            ],
            'scheduled_at' => now(),
        ];

        $notification = \App\Models\Notification::create($testNotification);

        return response()->json([
            'message' => 'Test notification sent successfully',
            'would_send' => true,
            'delivery_methods' => $preference->delivery_methods,
            'is_quiet_hours' => $preference->isInQuietHours(),
            'test_notification' => $notification,
        ]);
    }
}