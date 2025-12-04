<?php

namespace App\Http\Controllers;

use App\Models\CounselingAppointment;
use App\Models\CounselingInfo;
use App\Models\OrderItem;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="CounselingAppointment",
 *     type="object",
 *     title="CounselingAppointment",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="order_item_id", type="integer", nullable=true),
 *     @OA\Property(property="flip_course_case_id", type="integer", nullable=true),
 *     @OA\Property(property="counseling_info_id", type="integer"),
 *     @OA\Property(property="student_id", type="integer"),
 *     @OA\Property(property="counselor_id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="preferred_datetime", type="string", format="date-time"),
 *     @OA\Property(property="confirmed_datetime", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="duration", type="integer"),
 *     @OA\Property(property="method", type="string"),
 *     @OA\Property(property="location", type="string", nullable=true),
 *     @OA\Property(property="meeting_url", type="string", nullable=true),
 *     @OA\Property(property="counselor_notes", type="string", nullable=true),
 *     @OA\Property(property="student_feedback", type="string", nullable=true),
 *     @OA\Property(property="rating", type="integer", nullable=true),
 *     @OA\Property(property="is_urgent", type="boolean")
 * )
 */

class CounselingAppointmentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * @OA\Get(
     *     path="/counseling-appointments",
     *     tags={"Counseling Appointments"},
     *     summary="Get list of counseling appointments",
     *     description="Retrieve a list of counseling appointments with optional filters",
     *     @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         description="Filter by student ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="counselor_id",
     *         in="query",
     *         description="Filter by counselor ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by appointment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "completed", "cancelled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/CounselingAppointment")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = CounselingAppointment::with(['orderItem', 'counselingInfo', 'student', 'counselor', 'flipCourseCase']);

        // 篩選條件
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('counselor_id')) {
            $query->where('counselor_id', $request->counselor_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('flip_course_case_id')) {
            $query->where('flip_course_case_id', $request->flip_course_case_id);
        }

        $appointments = $query->orderBy('preferred_datetime', 'desc')->get();
        return response()->json($appointments);
    }

    /**
     * @OA\Post(
     *     path="/counseling-appointments",
     *     tags={"Counseling Appointments"},
     *     summary="Create a new counseling appointment",
     *     description="Create a new counseling appointment booking",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_item_id", "counseling_info_id", "student_id", "counselor_id", "title", "type", "preferred_datetime", "method"},
     *             @OA\Property(property="order_item_id", type="integer", description="Order item ID"),
     *             @OA\Property(property="counseling_info_id", type="integer", description="Counseling service ID"),
     *             @OA\Property(property="student_id", type="integer", description="Student member ID"),
     *             @OA\Property(property="counselor_id", type="integer", description="Counselor member ID"),
     *             @OA\Property(property="title", type="string", maxLength=255, description="Appointment title"),
     *             @OA\Property(property="description", type="string", description="Appointment description"),
     *             @OA\Property(property="type", type="string", enum={"academic", "career", "personal", "other"}, description="Counseling type"),
     *             @OA\Property(property="preferred_datetime", type="string", format="date-time", description="Preferred date and time"),
     *             @OA\Property(property="duration", type="integer", minimum=15, maximum=480, description="Duration in minutes"),
     *             @OA\Property(property="method", type="string", enum={"online", "offline"}, description="Counseling method"),
     *             @OA\Property(property="location", type="string", maxLength=255, description="Location for offline sessions"),
     *             @OA\Property(property="is_urgent", type="boolean", description="Whether this is urgent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Appointment created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CounselingAppointment")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or business logic error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_item_id' => 'nullable|exists:order_items,id',
            'flip_course_case_id' => 'nullable|exists:flip_course_cases,id',
            'counseling_info_id' => 'nullable|exists:counseling_infos,id',
            'student_id' => 'required|exists:members,id',
            'counselor_id' => 'nullable|exists:members,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:academic,career,personal,other',
            'preferred_datetime' => 'required|date|after:now',
            'duration' => 'integer|min:15|max:480',
            'method' => 'required|in:online,offline',
            'location' => 'nullable|string|max:255',
            'meeting_url' => 'nullable|url',
            'is_urgent' => 'boolean',
        ]);

        // 必須提供 order_item_id 或 flip_course_case_id 其中之一
        if (!isset($validated['order_item_id']) && !isset($validated['flip_course_case_id'])) {
            return response()->json(['error' => 'Either order_item_id or flip_course_case_id is required.'], 400);
        }

        $counselorId = $validated['counselor_id'] ?? null;
        $counselingInfo = null;
        $flipCourseCase = null;

        // 處理翻轉課程諮商
        if (isset($validated['flip_course_case_id']) && $validated['flip_course_case_id']) {
            $flipCourseCase = \App\Models\FlipCourseCase::findOrFail($validated['flip_course_case_id']);

            // 使用案例中已指派的諮商師
            if (!$flipCourseCase->counselor_id) {
                return response()->json(['error' => 'Counselor has not been assigned to this flip course case yet.'], 400);
            }
            $counselorId = $flipCourseCase->counselor_id;

            // 翻轉課程諮商不需要 counseling_info_id（可選）
            if (isset($validated['counseling_info_id']) && $validated['counseling_info_id']) {
                $counselingInfo = CounselingInfo::findOrFail($validated['counseling_info_id']);
            }
        }
        // 處理一般諮商
        else {
            // 驗證用戶是否購買了該諮商服務
            $orderItem = OrderItem::with(['product', 'order'])->findOrFail($validated['order_item_id']);
            $counselingInfo = CounselingInfo::findOrFail($validated['counseling_info_id']);

            if ($orderItem->product_id !== $counselingInfo->product_id) {
                return response()->json(['error' => 'Order item does not match counseling service.'], 400);
            }

            // 驗證訂單是否已支付
            if ($orderItem->order->status !== 'completed') {
                return response()->json(['error' => 'Order must be completed before scheduling counseling.'], 400);
            }

            // 檢查是否為翻轉課程訂單
            $flipCourseCase = \App\Models\FlipCourseCase::where('order_id', $orderItem->order_id)->first();

            if ($flipCourseCase) {
                // 如果透過 order_item_id 但實際是翻轉課程，使用案例中的諮商師
                if (!$flipCourseCase->counselor_id) {
                    return response()->json(['error' => 'Counselor has not been assigned to this flip course case yet.'], 400);
                }
                $counselorId = $flipCourseCase->counselor_id;
            } else {
                // 一般諮商：必須提供 counselor_id
                if (!$counselorId) {
                    return response()->json(['error' => 'counselor_id is required for regular counseling appointments.'], 400);
                }

                // 驗證諮商師是否可以提供此服務
                if (!$counselingInfo->counselors()->where('counselor_id', $counselorId)->exists()) {
                    return response()->json(['error' => 'Counselor is not available for this service.'], 400);
                }
            }
        }

        $appointment = CounselingAppointment::create(array_merge($validated, [
            'counselor_id' => $counselorId,
            'flip_course_case_id' => $flipCourseCase ? $flipCourseCase->id : null,
            'status' => 'pending',
            'duration' => $validated['duration'] ?? ($counselingInfo ? $counselingInfo->session_duration : 60),
        ]));

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor', 'flipCourseCase']), 201);
    }

    /**
     * @OA\Get(
     *     path="/counseling-appointments/{id}",
     *     tags={"Counseling Appointments"},
     *     summary="Get a specific counseling appointment",
     *     description="Retrieve details of a specific counseling appointment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Appointment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/CounselingAppointment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found"
     *     )
     * )
     */
    public function show($id)
    {
        $appointment = CounselingAppointment::with(['orderItem', 'counselingInfo', 'student', 'counselor', 'flipCourseCase'])
            ->findOrFail($id);
        return response()->json($appointment);
    }

    /**
     * @OA\Put(
     *     path="/counseling-appointments/{id}",
     *     tags={"Counseling Appointments"},
     *     summary="Update a counseling appointment",
     *     description="Update an existing counseling appointment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Appointment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "completed", "cancelled"}),
     *             @OA\Property(property="type", type="string", enum={"academic", "career", "personal", "other"}),
     *             @OA\Property(property="preferred_datetime", type="string", format="date-time"),
     *             @OA\Property(property="confirmed_datetime", type="string", format="date-time"),
     *             @OA\Property(property="duration", type="integer", minimum=15, maximum=480),
     *             @OA\Property(property="method", type="string", enum={"online", "offline"}),
     *             @OA\Property(property="location", type="string", maxLength=255),
     *             @OA\Property(property="meeting_url", type="string", format="uri"),
     *             @OA\Property(property="counselor_notes", type="string"),
     *             @OA\Property(property="student_feedback", type="string"),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="is_urgent", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CounselingAppointment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $appointment = CounselingAppointment::findOrFail($id);
        $oldStatus = $appointment->status;
        $oldTime = $appointment->confirmed_datetime ?: $appointment->preferred_datetime;

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,confirmed,completed,cancelled',
            'type' => 'in:academic,career,personal,other',
            'preferred_datetime' => 'date|after:now',
            'confirmed_datetime' => 'nullable|date',
            'duration' => 'integer|min:15|max:480',
            'method' => 'in:online,offline',
            'location' => 'nullable|string|max:255',
            'meeting_url' => 'nullable|url',
            'counselor_notes' => 'nullable|string',
            'student_feedback' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'is_urgent' => 'boolean',
        ]);

        $appointment->update($validated);

        // 檢查狀態是否變更，發送通知
        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $this->notificationService->createCounselingStatusChangeNotifications(
                $appointment->id,
                $oldStatus,
                $validated['status']
            );
        }

        // 檢查時間是否變更，發送通知
        $newTime = $validated['confirmed_datetime'] ?? $validated['preferred_datetime'] ?? null;
        if ($newTime && $newTime !== $oldTime) {
            $this->notificationService->createCounselingTimeChangeNotifications(
                $appointment->id,
                $oldTime,
                $newTime
            );
        }

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor', 'flipCourseCase']));
    }

    /**
     * @OA\Delete(
     *     path="/counseling-appointments/{id}",
     *     tags={"Counseling Appointments"},
     *     summary="Cancel a counseling appointment",
     *     description="Cancel a pending counseling appointment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Appointment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Appointment cancelled successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only pending appointments can be cancelled"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $appointment = CounselingAppointment::findOrFail($id);
        
        // 只允許取消待確認的預約
        if ($appointment->status !== 'pending') {
            return response()->json(['error' => 'Only pending appointments can be cancelled.'], 400);
        }

        $appointment->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Appointment cancelled successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/counseling-appointments/{id}/confirm",
     *     tags={"Counseling Appointments"},
     *     summary="Confirm a counseling appointment",
     *     description="Counselor confirms a pending appointment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Appointment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"counselor_id", "confirmed_datetime"},
     *             @OA\Property(property="counselor_id", type="integer", description="Counselor ID"),
     *             @OA\Property(property="confirmed_datetime", type="string", format="date-time", description="Confirmed date and time"),
     *             @OA\Property(property="meeting_url", type="string", format="uri", description="Meeting URL for online sessions"),
     *             @OA\Property(property="location", type="string", maxLength=255, description="Location for offline sessions")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment confirmed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CounselingAppointment")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only pending appointments can be confirmed"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Only the assigned counselor can confirm"
     *     )
     * )
     */
    public function confirm(Request $request, $id)
    {
        $appointment = CounselingAppointment::findOrFail($id);

        if ($appointment->status !== 'pending') {
            return response()->json(['error' => 'Only pending appointments can be confirmed.'], 400);
        }

        $validated = $request->validate([
            'counselor_id' => 'required|exists:members,id',
            'confirmed_datetime' => 'required|date|after:now',
            'meeting_url' => 'nullable|url',
            'location' => 'nullable|string|max:255',
        ]);

        // 驗證是否為該預約的諮商師
        if ($appointment->counselor_id !== $validated['counselor_id']) {
            return response()->json(['error' => 'Only the assigned counselor can confirm this appointment.'], 403);
        }

        $appointment->update(array_merge($validated, [
            'status' => 'confirmed'
        ]));

        // 發送確認通知
        $this->notificationService->createCounselingConfirmationNotifications($appointment->id);

        // 自動創建提醒通知（1小時前）
        $this->notificationService->createCounselingReminderNotifications($appointment->id, 60);

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor', 'flipCourseCase']));
    }

    /**
     * @OA\Post(
     *     path="/counseling-appointments/{id}/reject",
     *     tags={"Counseling Appointments"},
     *     summary="Reject a counseling appointment",
     *     description="Counselor rejects a pending appointment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Appointment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"counselor_id"},
     *             @OA\Property(property="counselor_id", type="integer", description="Counselor ID"),
     *             @OA\Property(property="counselor_notes", type="string", description="Reason for rejection")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment rejected successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CounselingAppointment")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only pending appointments can be rejected"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Only the assigned counselor can reject"
     *     )
     * )
     */
    public function reject(Request $request, $id)
    {
        $appointment = CounselingAppointment::findOrFail($id);

        if ($appointment->status !== 'pending') {
            return response()->json(['error' => 'Only pending appointments can be rejected.'], 400);
        }

        $validated = $request->validate([
            'counselor_id' => 'required|exists:members,id',
            'counselor_notes' => 'nullable|string',
        ]);

        // 驗證是否為該預約的諮商師
        if ($appointment->counselor_id !== $validated['counselor_id']) {
            return response()->json(['error' => 'Only the assigned counselor can reject this appointment.'], 403);
        }

        $appointment->update([
            'status' => 'cancelled',
            'counselor_notes' => $validated['counselor_notes'] ?? null,
        ]);

        // 發送拒絕通知
        $this->notificationService->createCounselingStatusChangeNotifications(
            $appointment->id,
            'pending',
            'cancelled'
        );

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor', 'flipCourseCase']));
    }

    /**
     * @OA\Post(
     *     path="/counseling-appointments/{id}/complete",
     *     tags={"Counseling Appointments"},
     *     summary="Mark appointment as completed",
     *     description="Mark a confirmed appointment as completed",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Appointment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="counselor_notes", type="string", description="Counselor notes"),
     *             @OA\Property(property="student_feedback", type="string", description="Student feedback"),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, description="Student rating")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment completed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CounselingAppointment")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only confirmed appointments can be completed"
     *     )
     * )
     */
    public function complete(Request $request, $id)
    {
        $appointment = CounselingAppointment::findOrFail($id);

        if ($appointment->status !== 'confirmed') {
            return response()->json(['error' => 'Only confirmed appointments can be completed.'], 400);
        }

        $validated = $request->validate([
            'counselor_notes' => 'nullable|string',
            'student_feedback' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $appointment->update(array_merge($validated, [
            'status' => 'completed'
        ]));

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor', 'flipCourseCase']));
    }
}
