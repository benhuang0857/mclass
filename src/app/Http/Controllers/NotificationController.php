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
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get user notifications",
     *     description="Retrieve notifications for a specific member with filtering and pagination",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="member_id",
     *         in="query",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="unread_only",
     *         in="query",
     *         description="Show only unread notifications",
     *         @OA\Schema(type="string", enum={"true", "false", "1", "0"}, example="false")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by notification type",
     *         @OA\Schema(type="string", enum={"course_reminder", "course_change", "counseling_reminder", "counseling_confirmed", "course_follower", "counselor_specific", "flip_case_assigned", "flip_task_assigned", "flip_prescription_issued", "flip_analysis_completed", "flip_cycle_started", "flip_case_completed"}, example="course_reminder")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="notifications", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="course_reminder"),
     *                     @OA\Property(property="title", type="string", example="Course Starting Soon"),
     *                     @OA\Property(property="content", type="string", example="Your class begins in 1 hour"),
     *                     @OA\Property(property="is_read", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-04 09:00:00")
     *                 )
     *             ),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=95)
     *             ),
     *             @OA\Property(property="stats", type="object",
     *                 @OA\Property(property="total_count", type="integer", example=95),
     *                 @OA\Property(property="unread_count", type="integer", example=12)
     *             )
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
     * 獲取用戶的通知列表
     */
    public function index(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'unread_only' => 'sometimes|in:true,false,1,0',
            'type' => 'string|in:course_reminder,course_change,counseling_reminder,counseling_confirmed,course_follower,counselor_specific,flip_case_assigned,flip_task_assigned,flip_prescription_issued,flip_analysis_completed,flip_cycle_started,flip_case_completed',
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
     * @OA\Get(
     *     path="/api/notifications/{id}",
     *     summary="Get notification details",
     *     description="Retrieve details of a specific notification",
     *     operationId="getNotification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", example="course_reminder"),
     *             @OA\Property(property="title", type="string", example="Course Starting Soon"),
     *             @OA\Property(property="content", type="string", example="Your class begins in 1 hour"),
     *             @OA\Property(property="is_read", type="boolean", example=false),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-04 09:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
     * 獲取單一通知詳情
     */
    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        return response()->json($notification);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/{id}/read",
     *     summary="Mark notification as read",
     *     description="Mark a specific notification as read",
     *     operationId="markNotificationAsRead",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Notification marked as read"),
     *             @OA\Property(property="notification", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="is_read", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
     * @OA\Put(
     *     path="/api/notifications/batch/read",
     *     summary="Mark multiple notifications as read",
     *     description="Mark multiple notifications as read in one request",
     *     operationId="markMultipleNotificationsAsRead",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Notification IDs and member ID",
     *         @OA\JsonContent(
     *             required={"notification_ids", "member_id"},
     *             @OA\Property(property="notification_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3, 4}),
     *             @OA\Property(property="member_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications marked as read successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Marked 4 notifications as read"),
     *             @OA\Property(property="updated_count", type="integer", example=4)
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
     * @OA\Put(
     *     path="/api/notifications/all/read",
     *     summary="Mark all notifications as read",
     *     description="Mark all notifications for a member as read",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Notifications"},
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
     *         description="All notifications marked as read successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Marked all notifications as read"),
     *             @OA\Property(property="updated_count", type="integer", example=12)
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
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     summary="Delete notification",
     *     description="Delete a specific notification",
     *     operationId="deleteNotification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Notification deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
     * @OA\Post(
     *     path="/api/notifications/trigger/course-reminder",
     *     summary="Trigger course reminder notification (Test)",
     *     description="Manually trigger course reminder notifications for testing purposes",
     *     operationId="triggerCourseReminder",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Course reminder data",
     *         @OA\JsonContent(
     *             required={"course_id"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="minutes_before", type="integer", minimum=1, maximum=1440, example=60)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course reminder notifications created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Course reminder notifications created"),
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="minutes_before", type="integer", example=60)
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
     * @OA\Post(
     *     path="/api/notifications/trigger/counseling-reminder",
     *     summary="Trigger counseling reminder notification (Test)",
     *     description="Manually trigger counseling reminder notifications for testing purposes",
     *     operationId="triggerCounselingReminder",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Counseling reminder data",
     *         @OA\JsonContent(
     *             required={"appointment_id"},
     *             @OA\Property(property="appointment_id", type="integer", example=1),
     *             @OA\Property(property="minutes_before", type="integer", minimum=1, maximum=1440, example=60)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counseling reminder notifications created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Counseling reminder notifications created"),
     *             @OA\Property(property="appointment_id", type="integer", example=1),
     *             @OA\Property(property="minutes_before", type="integer", example=60)
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
     * @OA\Get(
     *     path="/api/notifications/stats/summary",
     *     summary="Get notification statistics",
     *     description="Retrieve notification statistics for a specific member",
     *     operationId="getNotificationStats",
     *     tags={"Notifications"},
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
     *             @OA\Property(property="total_notifications", type="integer", example=95),
     *             @OA\Property(property="unread_notifications", type="integer", example=12),
     *             @OA\Property(property="notifications_by_type", type="object",
     *                 @OA\Property(property="course_reminder", type="integer", example=25),
     *                 @OA\Property(property="counseling_reminder", type="integer", example=15),
     *                 @OA\Property(property="flip_case_assigned", type="integer", example=5)
     *             ),
     *             @OA\Property(property="recent_notifications", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="course_reminder"),
     *                     @OA\Property(property="title", type="string", example="Course Starting Soon"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-04 09:00:00")
     *                 )
     *             )
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
     * @OA\Post(
     *     path="/api/notifications/trigger/counseling-confirmation",
     *     summary="Trigger counseling confirmation notification (Test)",
     *     description="Manually trigger counseling confirmation notifications for testing purposes",
     *     operationId="triggerCounselingConfirmation",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Counseling confirmation data",
     *         @OA\JsonContent(
     *             required={"appointment_id"},
     *             @OA\Property(property="appointment_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counseling confirmation notifications created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Counseling confirmation notifications created"),
     *             @OA\Property(property="appointment_id", type="integer", example=1)
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
     * @OA\Post(
     *     path="/api/notifications/trigger/counseling-status-change",
     *     summary="Trigger counseling status change notification (Test)",
     *     description="Manually trigger counseling status change notifications for testing purposes",
     *     operationId="triggerCounselingStatusChange",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Counseling status change data",
     *         @OA\JsonContent(
     *             required={"appointment_id", "old_status", "new_status"},
     *             @OA\Property(property="appointment_id", type="integer", example=1),
     *             @OA\Property(property="old_status", type="string", example="pending"),
     *             @OA\Property(property="new_status", type="string", example="confirmed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counseling status change notifications created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Counseling status change notifications created"),
     *             @OA\Property(property="appointment_id", type="integer", example=1),
     *             @OA\Property(property="status_change", type="string", example="pending → confirmed")
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
     * @OA\Post(
     *     path="/api/notifications/trigger/counseling-time-change",
     *     summary="Trigger counseling time change notification (Test)",
     *     description="Manually trigger counseling time change notifications for testing purposes",
     *     operationId="triggerCounselingTimeChange",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Counseling time change data",
     *         @OA\JsonContent(
     *             required={"appointment_id", "old_time", "new_time"},
     *             @OA\Property(property="appointment_id", type="integer", example=1),
     *             @OA\Property(property="old_time", type="string", format="date-time", example="2025-12-04 10:00:00"),
     *             @OA\Property(property="new_time", type="string", format="date-time", example="2025-12-04 14:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counseling time change notifications created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Counseling time change notifications created"),
     *             @OA\Property(property="appointment_id", type="integer", example=1),
     *             @OA\Property(property="time_change", type="string", example="2025-12-04 10:00:00 → 2025-12-04 14:00:00")
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
     * @OA\Post(
     *     path="/api/notifications/trigger/counselor-new-service",
     *     summary="Trigger counselor new service notification (Test)",
     *     description="Manually trigger counselor new service notifications for testing purposes",
     *     operationId="triggerCounselorNewService",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Counselor new service data",
     *         @OA\JsonContent(
     *             required={"counselor_id", "counseling_info_id"},
     *             @OA\Property(property="counselor_id", type="integer", example=2),
     *             @OA\Property(property="counseling_info_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counselor new service notifications created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Counselor new service notifications created"),
     *             @OA\Property(property="counselor_id", type="integer", example=2),
     *             @OA\Property(property="counseling_info_id", type="integer", example=1)
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