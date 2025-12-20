<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\Member;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/notification-preferences",
     *     summary="Get notification preferences",
     *     description="Retrieve notification preferences for a specific member with available options",
     *     operationId="getNotificationPreferences",
     *     tags={"Notification Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="member_id",
     *         in="query",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="preferences", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="member_id", type="integer", example=1),
     *                     @OA\Property(property="notification_type", type="string", example="course_reminder"),
     *                     @OA\Property(property="enabled", type="boolean", example=true),
     *                     @OA\Property(property="delivery_methods", type="array", @OA\Items(type="string"), example={"database", "email"}),
     *                     @OA\Property(property="advance_minutes", type="integer", example=60)
     *                 )
     *             ),
     *             @OA\Property(property="available_delivery_methods", type="object"),
     *             @OA\Property(property="available_notification_types", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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

                // 翻轉課程相關
                'flip_case_assigned' => '翻轉課程案例指派',
                'flip_task_assigned' => '翻轉課程任務指派',
                'flip_prescription_issued' => '學習處方簽開立',
                'flip_analysis_completed' => '學習分析報告完成',
                'flip_cycle_started' => '新學習循環開始',
                'flip_case_completed' => '翻轉課程案例完成',

                // 總開關
                'all' => '所有通知總開關',
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/notification-preferences/{id}",
     *     summary="Update notification preference",
     *     description="Update a specific notification preference setting",
     *     operationId="updateNotificationPreference",
     *     tags={"Notification Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification preference ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Preference update data",
     *         @OA\JsonContent(
     *             @OA\Property(property="enabled", type="boolean", example=true),
     *             @OA\Property(property="delivery_methods", type="array", @OA\Items(type="string", enum={"database", "email", "push", "sms"}), example={"database", "email"}),
     *             @OA\Property(property="advance_minutes", type="integer", minimum=1, maximum=10080, example=60),
     *             @OA\Property(property="schedule_settings", type="object",
     *                 @OA\Property(property="quiet_hours", type="object",
     *                     @OA\Property(property="start", type="string", format="time", example="22:00"),
     *                     @OA\Property(property="end", type="string", format="time", example="07:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification preference updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Notification preference updated successfully"),
     *             @OA\Property(property="preference", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Preference not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
     * @OA\Put(
     *     path="/notification-preferences/batch/update",
     *     summary="Batch update notification preferences",
     *     description="Update multiple notification preferences at once",
     *     operationId="batchUpdateNotificationPreferences",
     *     tags={"Notification Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Batch preference update data",
     *         @OA\JsonContent(
     *             required={"member_id", "preferences"},
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="preferences", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="notification_type", type="string", example="course_reminder"),
     *                     @OA\Property(property="enabled", type="boolean", example=true),
     *                     @OA\Property(property="delivery_methods", type="array", @OA\Items(type="string"), example={"database", "email"}),
     *                     @OA\Property(property="advance_minutes", type="integer", example=60)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successfully updated 5 preferences"),
     *             @OA\Property(property="updated_count", type="integer", example=5),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
     * @OA\Post(
     *     path="/notification-preferences/reset-defaults",
     *     summary="Reset preferences to defaults",
     *     description="Reset all notification preferences to default settings for a member",
     *     operationId="resetNotificationPreferencesToDefaults",
     *     tags={"Notification Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Member ID",
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(property="member_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences reset successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Notification preferences reset to defaults"),
     *             @OA\Property(property="preferences", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
     * @OA\Post(
     *     path="/notification-preferences/quick-setting",
     *     summary="Quick preference settings",
     *     description="Quickly enable all, disable all, or enable only important notifications",
     *     operationId="quickNotificationSettings",
     *     tags={"Notification Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Quick setting action",
     *         @OA\JsonContent(
     *             required={"member_id", "action"},
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="action", type="string", enum={"enable_all", "disable_all", "enable_important_only"}, example="enable_important_only")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quick setting applied successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Only important notifications enabled"),
     *             @OA\Property(property="preferences", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
                    'flip_case_assigned',
                    'flip_task_assigned',
                    'flip_prescription_issued',
                    'flip_analysis_completed',
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
     * @OA\Post(
     *     path="/notification-preferences/test",
     *     summary="Test notification settings",
     *     description="Test notification settings by sending a test notification",
     *     operationId="testNotificationSettings",
     *     tags={"Notification Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Test notification data",
     *         @OA\JsonContent(
     *             required={"member_id", "notification_type"},
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="notification_type", type="string", example="course_reminder")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test notification sent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Test notification sent successfully"),
     *             @OA\Property(property="would_send", type="boolean", example=true),
     *             @OA\Property(property="delivery_methods", type="array", @OA\Items(type="string"), example={"database", "email"}),
     *             @OA\Property(property="is_quiet_hours", type="boolean", example=false),
     *             @OA\Property(property="test_notification", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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