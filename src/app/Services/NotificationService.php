<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Member;
use App\Models\ClubCourse;
use App\Models\CounselingAppointment;
use Carbon\Carbon;

class NotificationService
{
    /**
     * 創建通知（考慮用戶偏好設定）
     */
    public function createNotification(array $data)
    {
        // 檢查用戶是否啟用此類型的通知
        if (!$this->isNotificationEnabled($data['member_id'], $data['type'])) {
            return null; // 用戶已關閉此類型通知
        }

        // 根據用戶偏好調整提前時間
        if (isset($data['minutes_before'])) {
            $userPreference = $this->getUserPreference($data['member_id'], $data['type']);
            if ($userPreference && $userPreference->advance_minutes) {
                $scheduledTime = Carbon::parse($data['original_time'] ?? $data['scheduled_at'])
                    ->subMinutes($userPreference->advance_minutes);
                $data['scheduled_at'] = $scheduledTime;
            }
        }

        // 檢查靜音時間
        $scheduledAt = Carbon::parse($data['scheduled_at'] ?? now());
        if ($this->isInQuietHours($data['member_id'], $data['type'], $scheduledAt)) {
            // 延後到非靜音時間
            $data['scheduled_at'] = $this->adjustForQuietHours($data['member_id'], $data['type'], $scheduledAt);
        }

        return Notification::create([
            'member_id' => $data['member_id'],
            'type' => $data['type'],
            'related_type' => $data['related_type'],
            'related_id' => $data['related_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'data' => $data['data'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? now(),
        ]);
    }

    /**
     * 批量創建通知
     */
    public function createBulkNotifications(array $members, array $notificationData)
    {
        $notifications = [];
        foreach ($members as $member) {
            $notifications[] = array_merge($notificationData, [
                'member_id' => $member->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Notification::insert($notifications);
    }

    /**
     * 課程開始提醒
     */
    public function createCourseReminderNotifications($courseId, $minutesBefore = 60)
    {
        $course = ClubCourse::with('courseInfo')->findOrFail($courseId);
        
        // 獲取購買該課程的用戶
        $purchasers = $this->getCoursePurchasers($courseId);
        
        $scheduledTime = Carbon::parse($course->start_time)->subMinutes($minutesBefore);
        
        $notificationData = [
            'type' => 'course_reminder',
            'related_type' => 'course',
            'related_id' => $courseId,
            'title' => "課程即將開始",
            'content' => "您報名的課程「{$course->courseInfo->name}」將在 {$minutesBefore} 分鐘後開始",
            'data' => [
                'course_name' => $course->courseInfo->name,
                'start_time' => $course->start_time,
                'location' => $course->location,
                'link' => $course->link,
            ],
            'scheduled_at' => $scheduledTime,
        ];

        return $this->createBulkNotifications($purchasers, $notificationData);
    }

    /**
     * 課程時間變更通知
     */
    public function createCourseChangeNotifications($courseId, $changeType, $oldValue = null, $newValue = null)
    {
        $course = ClubCourse::with('courseInfo')->findOrFail($courseId);
        
        // 獲取購買該課程的用戶和追蹤者
        $purchasers = $this->getCoursePurchasers($courseId);
        $followers = $this->getCourseFollowers($courseId);
        $allUsers = $purchasers->merge($followers)->unique('id');

        $changeMessages = [
            'time' => "上課時間已變更",
            'location' => "上課地點已變更",
            'cancellation' => "課程已取消",
        ];

        $notificationData = [
            'type' => 'course_change',
            'related_type' => 'course',
            'related_id' => $courseId,
            'title' => $changeMessages[$changeType] ?? '課程資訊變更',
            'content' => "您關注的課程「{$course->courseInfo->name}」有重要異動，請查看詳情",
            'data' => [
                'course_name' => $course->courseInfo->name,
                'change_type' => $changeType,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'course_id' => $courseId,
            ],
            'scheduled_at' => now(),
        ];

        return $this->createBulkNotifications($allUsers, $notificationData);
    }

    /**
     * 諮商提醒通知
     */
    public function createCounselingReminderNotifications($appointmentId, $minutesBefore = 60)
    {
        $appointment = CounselingAppointment::with(['student', 'counselor', 'counselingInfo'])
            ->findOrFail($appointmentId);
        
        $appointmentTime = $appointment->confirmed_datetime ?: $appointment->preferred_datetime;
        $scheduledTime = Carbon::parse($appointmentTime)->subMinutes($minutesBefore);

        // 通知學生
        $studentNotification = [
            'member_id' => $appointment->student_id,
            'type' => 'counseling_reminder',
            'related_type' => 'counseling',
            'related_id' => $appointmentId,
            'title' => '諮商即將開始',
            'content' => "您預約的諮商將在 {$minutesBefore} 分鐘後開始",
            'data' => [
                'appointment_time' => $appointmentTime,
                'counselor_name' => $appointment->counselor->nickname,
                'service_name' => $appointment->counselingInfo->name,
                'location' => $appointment->location,
                'meeting_url' => $appointment->meeting_url,
            ],
            'scheduled_at' => $scheduledTime,
        ];

        // 通知諮商師
        $counselorNotification = [
            'member_id' => $appointment->counselor_id,
            'type' => 'counseling_reminder',
            'related_type' => 'counseling', 
            'related_id' => $appointmentId,
            'title' => '諮商即將開始',
            'content' => "您的諮商預約將在 {$minutesBefore} 分鐘後開始",
            'data' => [
                'appointment_time' => $appointmentTime,
                'student_name' => $appointment->student->nickname,
                'service_name' => $appointment->counselingInfo->name,
                'location' => $appointment->location,
                'meeting_url' => $appointment->meeting_url,
            ],
            'scheduled_at' => $scheduledTime,
        ];

        $this->createNotification($studentNotification);
        $this->createNotification($counselorNotification);
    }

    /**
     * 諮商預約確認通知
     */
    public function createCounselingConfirmationNotifications($appointmentId)
    {
        $appointment = CounselingAppointment::with(['student', 'counselor', 'counselingInfo'])
            ->findOrFail($appointmentId);

        $appointmentTime = $appointment->confirmed_datetime;

        // 通知學生預約已確認
        $studentNotification = [
            'member_id' => $appointment->student_id,
            'type' => 'counseling_confirmed',
            'related_type' => 'counseling',
            'related_id' => $appointmentId,
            'title' => '諮商預約已確認',
            'content' => "您的諮商預約已確認，預約時間：" . Carbon::parse($appointmentTime)->format('Y-m-d H:i'),
            'data' => [
                'appointment_time' => $appointmentTime,
                'counselor_name' => $appointment->counselor->nickname,
                'service_name' => $appointment->counselingInfo->name,
                'location' => $appointment->location,
                'meeting_url' => $appointment->meeting_url,
                'appointment_id' => $appointmentId,
            ],
            'scheduled_at' => now(),
        ];

        // 通知諮商師有新確認的預約
        $counselorNotification = [
            'member_id' => $appointment->counselor_id,
            'type' => 'counseling_confirmed',
            'related_type' => 'counseling',
            'related_id' => $appointmentId,
            'title' => '新的諮商預約確認',
            'content' => "您有一個新的諮商預約已確認，預約時間：" . Carbon::parse($appointmentTime)->format('Y-m-d H:i'),
            'data' => [
                'appointment_time' => $appointmentTime,
                'student_name' => $appointment->student->nickname,
                'service_name' => $appointment->counselingInfo->name,
                'location' => $appointment->location,
                'meeting_url' => $appointment->meeting_url,
                'appointment_id' => $appointmentId,
            ],
            'scheduled_at' => now(),
        ];

        $this->createNotification($studentNotification);
        $this->createNotification($counselorNotification);
    }

    /**
     * 諮商預約狀態變更通知
     */
    public function createCounselingStatusChangeNotifications($appointmentId, $oldStatus, $newStatus)
    {
        $appointment = CounselingAppointment::with(['student', 'counselor', 'counselingInfo'])
            ->findOrFail($appointmentId);

        $statusMessages = [
            'pending' => '待確認',
            'confirmed' => '已確認',
            'completed' => '已完成',
            'cancelled' => '已取消',
        ];

        $oldStatusText = $statusMessages[$oldStatus] ?? $oldStatus;
        $newStatusText = $statusMessages[$newStatus] ?? $newStatus;

        // 通知學生狀態變更
        $studentNotification = [
            'member_id' => $appointment->student_id,
            'type' => 'counseling_status_change',
            'related_type' => 'counseling',
            'related_id' => $appointmentId,
            'title' => '諮商預約狀態變更',
            'content' => "您的諮商預約狀態已從「{$oldStatusText}」變更為「{$newStatusText}」",
            'data' => [
                'appointment_id' => $appointmentId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'counselor_name' => $appointment->counselor->nickname,
                'service_name' => $appointment->counselingInfo->name,
                'appointment_time' => $appointment->confirmed_datetime ?: $appointment->preferred_datetime,
            ],
            'scheduled_at' => now(),
        ];

        // 如果是取消狀態，也通知諮商師
        if ($newStatus === 'cancelled') {
            $counselorNotification = [
                'member_id' => $appointment->counselor_id,
                'type' => 'counseling_status_change',
                'related_type' => 'counseling',
                'related_id' => $appointmentId,
                'title' => '諮商預約被取消',
                'content' => "學生 {$appointment->student->nickname} 的諮商預約已被取消",
                'data' => [
                    'appointment_id' => $appointmentId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'student_name' => $appointment->student->nickname,
                    'service_name' => $appointment->counselingInfo->name,
                    'appointment_time' => $appointment->confirmed_datetime ?: $appointment->preferred_datetime,
                ],
                'scheduled_at' => now(),
            ];

            $this->createNotification($counselorNotification);
        }

        $this->createNotification($studentNotification);
    }

    /**
     * 諮商時間變更通知
     */
    public function createCounselingTimeChangeNotifications($appointmentId, $oldTime, $newTime)
    {
        $appointment = CounselingAppointment::with(['student', 'counselor', 'counselingInfo'])
            ->findOrFail($appointmentId);

        $oldTimeFormatted = Carbon::parse($oldTime)->format('Y-m-d H:i');
        $newTimeFormatted = Carbon::parse($newTime)->format('Y-m-d H:i');

        // 通知學生時間變更
        $studentNotification = [
            'member_id' => $appointment->student_id,
            'type' => 'counseling_time_change',
            'related_type' => 'counseling',
            'related_id' => $appointmentId,
            'title' => '諮商時間變更',
            'content' => "您的諮商時間已從 {$oldTimeFormatted} 變更為 {$newTimeFormatted}",
            'data' => [
                'appointment_id' => $appointmentId,
                'old_time' => $oldTime,
                'new_time' => $newTime,
                'counselor_name' => $appointment->counselor->nickname,
                'service_name' => $appointment->counselingInfo->name,
            ],
            'scheduled_at' => now(),
        ];

        // 通知諮商師時間變更
        $counselorNotification = [
            'member_id' => $appointment->counselor_id,
            'type' => 'counseling_time_change',
            'related_type' => 'counseling',
            'related_id' => $appointmentId,
            'title' => '諮商時間變更',
            'content' => "與 {$appointment->student->nickname} 的諮商時間已從 {$oldTimeFormatted} 變更為 {$newTimeFormatted}",
            'data' => [
                'appointment_id' => $appointmentId,
                'old_time' => $oldTime,
                'new_time' => $newTime,
                'student_name' => $appointment->student->nickname,
                'service_name' => $appointment->counselingInfo->name,
            ],
            'scheduled_at' => now(),
        ];

        $this->createNotification($studentNotification);
        $this->createNotification($counselorNotification);
    }

    /**
     * 諮商師新服務通知（給曾經預約該諮商師的用戶）
     */
    public function createCounselorNewServiceNotifications($counselorId, $counselingInfoId)
    {
        $counselor = Member::findOrFail($counselorId);
        $counselingInfo = \App\Models\CounselingInfo::findOrFail($counselingInfoId);

        // 獲取曾經預約該諮商師的用戶
        $previousStudents = Member::whereHas('counselingAppointments', function($query) use ($counselorId) {
            $query->where('counselor_id', $counselorId)
                  ->whereIn('status', ['completed', 'confirmed']);
        })->distinct()->get();

        $notificationData = [
            'type' => 'counselor_new_service',
            'related_type' => 'counselor',
            'related_id' => $counselorId,
            'title' => '您關注的諮商師推出新服務',
            'content' => "諮商師 {$counselor->nickname} 推出了新的諮商服務：{$counselingInfo->name}",
            'data' => [
                'counselor_id' => $counselorId,
                'counselor_name' => $counselor->nickname,
                'counseling_info_id' => $counselingInfoId,
                'service_name' => $counselingInfo->name,
                'service_description' => $counselingInfo->description,
            ],
            'scheduled_at' => now(),
        ];

        $this->createBulkNotifications($previousStudents, $notificationData);
    }

    /**
     * 課程新班次開設通知（給追蹤該課程的用戶）
     */
    public function createCourseNewClassNotifications($courseId)
    {
        $course = ClubCourse::with('courseInfo')->findOrFail($courseId);
        
        // 獲取追蹤該課程商品的用戶
        $followers = $this->getCourseFollowers($courseId);
        
        if ($followers->isEmpty()) {
            return;
        }

        $notificationData = [
            'type' => 'course_new_class',
            'related_type' => 'course',
            'related_id' => $courseId,
            'title' => '您關注的課程開設新班次',
            'content' => "課程「{$course->courseInfo->name}」開設新班次，開課時間：" . Carbon::parse($course->start_time)->format('Y-m-d H:i'),
            'data' => [
                'course_name' => $course->courseInfo->name,
                'course_description' => $course->courseInfo->description,
                'start_time' => $course->start_time,
                'location' => $course->location,
                'link' => $course->link,
                'course_id' => $courseId,
                'course_info_id' => $course->course_id,
                'is_trial' => $course->trial,
            ],
            'scheduled_at' => now(),
        ];

        $this->createBulkNotifications($followers, $notificationData);
    }

    /**
     * 課程價格變動通知
     */
    public function createCoursePriceChangeNotifications($productId, $oldPrice, $newPrice)
    {
        $product = \App\Models\Product::with('clubCourseInfo')->findOrFail($productId);
        
        // 獲取追蹤該產品的用戶
        $followers = Member::whereHas('followedProducts', function($query) use ($productId) {
            $query->where('product_id', $productId);
        })->get();
        
        if ($followers->isEmpty()) {
            return;
        }

        $priceChange = $newPrice > $oldPrice ? '漲價' : '降價';
        $priceChangeIcon = $newPrice > $oldPrice ? '📈' : '📉';

        $notificationData = [
            'type' => 'course_price_change',
            'related_type' => 'product',
            'related_id' => $productId,
            'title' => "您關注的課程{$priceChange}了 {$priceChangeIcon}",
            'content' => "課程「{$product->name}」價格已從 \${$oldPrice} 變更為 \${$newPrice}",
            'data' => [
                'product_id' => $productId,
                'product_name' => $product->name,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'price_change_type' => $priceChange,
                'price_difference' => abs($newPrice - $oldPrice),
            ],
            'scheduled_at' => now(),
        ];

        $this->createBulkNotifications($followers, $notificationData);
    }

    /**
     * 課程狀態變更通知（如：售罄、重新開放）
     */
    public function createCourseStatusChangeNotifications($productId, $oldStatus, $newStatus)
    {
        $product = \App\Models\Product::with('clubCourseInfo')->findOrFail($productId);
        
        $followers = Member::whereHas('followedProducts', function($query) use ($productId) {
            $query->where('product_id', $productId);
        })->get();
        
        if ($followers->isEmpty()) {
            return;
        }

        $statusMessages = [
            'published' => '已發布',
            'unpublished' => '已下架',
            'sold-out' => '已售罄',
        ];

        $oldStatusText = $statusMessages[$oldStatus] ?? $oldStatus;
        $newStatusText = $statusMessages[$newStatus] ?? $newStatus;

        // 只對重要狀態變更發送通知
        $importantStatusChanges = [
            'unpublished' => 'published', // 重新上架
            'sold-out' => 'published',    // 重新開放
        ];

        $shouldNotify = isset($importantStatusChanges[$oldStatus]) && 
                       $importantStatusChanges[$oldStatus] === $newStatus;

        if (!$shouldNotify) {
            return;
        }

        $title = $newStatus === 'published' && $oldStatus === 'sold-out' 
            ? '您關注的課程重新開放報名！' 
            : '您關注的課程狀態更新';

        $notificationData = [
            'type' => 'course_status_change',
            'related_type' => 'product',
            'related_id' => $productId,
            'title' => $title,
            'content' => "課程「{$product->name}」狀態已從「{$oldStatusText}」變更為「{$newStatusText}」",
            'data' => [
                'product_id' => $productId,
                'product_name' => $product->name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            'scheduled_at' => now(),
        ];

        $this->createBulkNotifications($followers, $notificationData);
    }

    /**
     * 課程即將截止報名通知
     */
    public function createCourseRegistrationDeadlineNotifications($courseId, $hoursBeforeDeadline = 24)
    {
        $course = ClubCourse::with('courseInfo')->findOrFail($courseId);
        
        // 獲取追蹤該課程但尚未購買的用戶
        $followers = $this->getCourseFollowers($courseId);
        $purchasers = $this->getCoursePurchasers($courseId);
        
        // 排除已購買的用戶
        $nonPurchasers = $followers->diff($purchasers);
        
        if ($nonPurchasers->isEmpty()) {
            return;
        }

        $deadlineTime = Carbon::parse($course->start_time)->subHours($hoursBeforeDeadline);

        $notificationData = [
            'type' => 'course_registration_deadline',
            'related_type' => 'course',
            'related_id' => $courseId,
            'title' => '您關注的課程即將截止報名',
            'content' => "課程「{$course->courseInfo->name}」將在 {$hoursBeforeDeadline} 小時後開始，現在是最後報名機會！",
            'data' => [
                'course_name' => $course->courseInfo->name,
                'start_time' => $course->start_time,
                'deadline_hours' => $hoursBeforeDeadline,
                'course_id' => $courseId,
                'course_info_id' => $course->course_id,
            ],
            'scheduled_at' => $deadlineTime,
        ];

        $this->createBulkNotifications($nonPurchasers, $notificationData);
    }

    /**
     * 獲取課程購買者
     */
    private function getCoursePurchasers($courseId)
    {
        $course = ClubCourse::findOrFail($courseId);
        
        return Member::whereHas('orders.orderItems', function($query) use ($course) {
            $query->where('product_id', $course->course_id)
                  ->whereHas('order', function($q) {
                      $q->where('status', 'completed');
                  });
        })->get();
    }

    /**
     * 獲取課程追蹤者
     */
    private function getCourseFollowers($courseId)
    {
        $course = ClubCourse::findOrFail($courseId);
        
        return Member::whereHas('followedProducts', function($query) use ($course) {
            $query->where('product_id', $course->course_id);
        })->get();
    }

    /**
     * 發送待發送的通知
     */
    public function sendPendingNotifications()
    {
        $notifications = Notification::pending()->get();
        
        foreach ($notifications as $notification) {
            // 這裡可以整合各種發送方式（Email, Push, SMS 等）
            $this->sendNotification($notification);
            $notification->markAsSent();
        }

        return $notifications->count();
    }

    /**
     * 發送單一通知（基礎實作）
     */
    private function sendNotification(Notification $notification)
    {
        // 基礎實作：只記錄到資料庫
        // 未來可以整合 Email、Push Notification、SMS 等
        \Log::info("Notification sent", [
            'notification_id' => $notification->id,
            'member_id' => $notification->member_id,
            'type' => $notification->type,
            'title' => $notification->title,
        ]);
        
        return true;
    }

    /**
     * 檢查用戶是否啟用特定類型的通知
     */
    private function isNotificationEnabled($memberId, $notificationType)
    {
        $preference = $this->getUserPreference($memberId, $notificationType);
        
        if (!$preference) {
            // 如果沒有設定，檢查總開關
            $globalPreference = $this->getUserPreference($memberId, 'all');
            return $globalPreference ? $globalPreference->enabled : true; // 預設啟用
        }
        
        return $preference->enabled;
    }

    /**
     * 獲取用戶的通知偏好設定
     */
    private function getUserPreference($memberId, $notificationType)
    {
        return \App\Models\NotificationPreference::forMember($memberId)
            ->forType($notificationType)
            ->first();
    }

    /**
     * 檢查是否在靜音時間內
     */
    private function isInQuietHours($memberId, $notificationType, $dateTime)
    {
        $preference = $this->getUserPreference($memberId, $notificationType);
        
        if (!$preference) {
            return false;
        }
        
        return $preference->isInQuietHours($dateTime);
    }

    /**
     * 調整發送時間以避開靜音時間
     */
    private function adjustForQuietHours($memberId, $notificationType, $originalTime)
    {
        $preference = $this->getUserPreference($memberId, $notificationType);
        
        if (!$preference || !isset($preference->schedule_settings['quiet_hours'])) {
            return $originalTime;
        }
        
        $quietHours = $preference->schedule_settings['quiet_hours'];
        $endTime = $quietHours['end'] ?? '08:00';
        
        // 設定到靜音時間結束後發送
        $adjustedTime = $originalTime->copy()->setTimeFromTimeString($endTime);
        
        // 如果調整後的時間是過去時間，移到明天
        if ($adjustedTime->isPast()) {
            $adjustedTime->addDay();
        }
        
        return $adjustedTime;
    }
}