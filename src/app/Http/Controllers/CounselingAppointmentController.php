<?php

namespace App\Http\Controllers;

use App\Models\CounselingAppointment;
use App\Models\CounselingInfo;
use App\Models\OrderIteam;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CounselingAppointmentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    public function index(Request $request)
    {
        $query = CounselingAppointment::with(['orderItem', 'counselingInfo', 'student', 'counselor']);

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

        $appointments = $query->orderBy('preferred_datetime', 'desc')->get();
        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'counseling_info_id' => 'required|exists:counseling_infos,id',
            'student_id' => 'required|exists:members,id',
            'counselor_id' => 'required|exists:members,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:academic,career,personal,other',
            'preferred_datetime' => 'required|date|after:now',
            'duration' => 'integer|min:15|max:480',
            'method' => 'required|in:online,offline',
            'location' => 'nullable|string|max:255',
            'is_urgent' => 'boolean',
        ]);

        // 驗證用戶是否購買了該諮商服務
        $orderItem = OrderIteam::with(['product', 'order'])->findOrFail($validated['order_item_id']);
        $counselingInfo = CounselingInfo::findOrFail($validated['counseling_info_id']);

        if ($orderItem->product_id !== $counselingInfo->product_id) {
            return response()->json(['error' => 'Order item does not match counseling service.'], 400);
        }

        // 驗證諮商師是否可以提供此服務
        if (!$counselingInfo->counselors()->where('counselor_id', $validated['counselor_id'])->exists()) {
            return response()->json(['error' => 'Counselor is not available for this service.'], 400);
        }

        $appointment = CounselingAppointment::create(array_merge($validated, [
            'status' => 'pending',
            'duration' => $validated['duration'] ?? $counselingInfo->session_duration,
        ]));

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor']), 201);
    }

    public function show($id)
    {
        $appointment = CounselingAppointment::with(['orderItem', 'counselingInfo', 'student', 'counselor'])
            ->findOrFail($id);
        return response()->json($appointment);
    }

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

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor']));
    }

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

    public function confirm(Request $request, $id)
    {
        $appointment = CounselingAppointment::findOrFail($id);

        if ($appointment->status !== 'pending') {
            return response()->json(['error' => 'Only pending appointments can be confirmed.'], 400);
        }

        $validated = $request->validate([
            'confirmed_datetime' => 'required|date|after:now',
            'meeting_url' => 'nullable|url',
            'location' => 'nullable|string|max:255',
        ]);

        $appointment->update(array_merge($validated, [
            'status' => 'confirmed'
        ]));

        // 發送確認通知
        $this->notificationService->createCounselingConfirmationNotifications($appointment->id);

        // 自動創建提醒通知（1小時前）
        $this->notificationService->createCounselingReminderNotifications($appointment->id, 60);

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor']));
    }

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

        return response()->json($appointment->load(['orderItem', 'counselingInfo', 'student', 'counselor']));
    }
}
