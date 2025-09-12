<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * 獲取用戶的通知列表
     */
    public function index(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'unread_only' => 'boolean',
            'type' => 'string|in:course_reminder,course_change,counseling_reminder,counseling_confirmed,course_follower,counselor_specific',
            'limit' => 'integer|min:1|max:100',
        ]);

        $query = Notification::forMember($request->member_id)
            ->orderBy('created_at', 'desc');

        // 篩選條件
        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $limit = $request->input('limit', 20);
        $notifications = $query->paginate($limit);

        // 統計資料
        $stats = [
            'total_count' => Notification::forMember($request->member_id)->count(),
            'unread_count' => Notification::forMember($request->member_id)->unread()->count(),
        ];

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * 獲取單一通知詳情
     */
    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        return response()->json($notification);
    }

    /**
     * 標記通知為已讀
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * 批量標記通知為已讀
     */
    public function markMultipleAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
            'member_id' => 'required|exists:members,id',
        ]);

        $updated = Notification::whereIn('id', $request->notification_ids)
            ->where('member_id', $request->member_id)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => "Marked {$updated} notifications as read",
            'updated_count' => $updated,
        ]);
    }

    /**
     * 標記所有通知為已讀
     */
    public function markAllAsRead(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $updated = Notification::forMember($request->member_id)
            ->unread()
            ->update(['is_read' => true]);

        return response()->json([
            'message' => "Marked all notifications as read",
            'updated_count' => $updated,
        ]);
    }

    /**
     * 刪除通知
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * 手動觸發課程提醒（測試用）
     */
    public function triggerCourseReminder(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:club_courses,id',
            'minutes_before' => 'integer|min:1|max:1440', // 最多提前24小時
        ]);

        $minutesBefore = $request->input('minutes_before', 60);
        
        $this->notificationService->createCourseReminderNotifications(
            $request->course_id, 
            $minutesBefore
        );

        return response()->json([
            'message' => 'Course reminder notifications created',
            'course_id' => $request->course_id,
            'minutes_before' => $minutesBefore,
        ]);
    }

    /**
     * 手動觸發諮商提醒（測試用）
     */
    public function triggerCounselingReminder(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:counseling_appointments,id',
            'minutes_before' => 'integer|min:1|max:1440',
        ]);

        $minutesBefore = $request->input('minutes_before', 60);
        
        $this->notificationService->createCounselingReminderNotifications(
            $request->appointment_id,
            $minutesBefore
        );

        return response()->json([
            'message' => 'Counseling reminder notifications created',
            'appointment_id' => $request->appointment_id,
            'minutes_before' => $minutesBefore,
        ]);
    }

    /**
     * 獲取通知統計
     */
    public function getStats(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $memberId = $request->member_id;

        $stats = [
            'total_notifications' => Notification::forMember($memberId)->count(),
            'unread_notifications' => Notification::forMember($memberId)->unread()->count(),
            'notifications_by_type' => Notification::forMember($memberId)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'recent_notifications' => Notification::forMember($memberId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * 手動觸發諮商確認通知（測試用）
     */
    public function triggerCounselingConfirmation(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:counseling_appointments,id',
        ]);

        $this->notificationService->createCounselingConfirmationNotifications(
            $request->appointment_id
        );

        return response()->json([
            'message' => 'Counseling confirmation notifications created',
            'appointment_id' => $request->appointment_id,
        ]);
    }

    /**
     * 手動觸發諮商狀態變更通知（測試用）
     */
    public function triggerCounselingStatusChange(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:counseling_appointments,id',
            'old_status' => 'required|string',
            'new_status' => 'required|string',
        ]);

        $this->notificationService->createCounselingStatusChangeNotifications(
            $request->appointment_id,
            $request->old_status,
            $request->new_status
        );

        return response()->json([
            'message' => 'Counseling status change notifications created',
            'appointment_id' => $request->appointment_id,
            'status_change' => "{$request->old_status} → {$request->new_status}",
        ]);
    }

    /**
     * 手動觸發諮商時間變更通知（測試用）
     */
    public function triggerCounselingTimeChange(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:counseling_appointments,id',
            'old_time' => 'required|date',
            'new_time' => 'required|date',
        ]);

        $this->notificationService->createCounselingTimeChangeNotifications(
            $request->appointment_id,
            $request->old_time,
            $request->new_time
        );

        return response()->json([
            'message' => 'Counseling time change notifications created',
            'appointment_id' => $request->appointment_id,
            'time_change' => "{$request->old_time} → {$request->new_time}",
        ]);
    }

    /**
     * 手動觸發諮商師新服務通知（測試用）
     */
    public function triggerCounselorNewService(Request $request)
    {
        $request->validate([
            'counselor_id' => 'required|exists:members,id',
            'counseling_info_id' => 'required|exists:counseling_infos,id',
        ]);

        $this->notificationService->createCounselorNewServiceNotifications(
            $request->counselor_id,
            $request->counseling_info_id
        );

        return response()->json([
            'message' => 'Counselor new service notifications created',
            'counselor_id' => $request->counselor_id,
            'counseling_info_id' => $request->counseling_info_id,
        ]);
    }
}