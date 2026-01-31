<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NoticeTypeController;
use App\Http\Controllers\InvitationCodeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LevelTypeController;
use App\Http\Controllers\LangTypeController;
use App\Http\Controllers\TechMethodTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CourseInfoTypeController;
use App\Http\Controllers\CourseStatusTypeController;
use App\Http\Controllers\ClubCourseInfoController;
use App\Http\Controllers\ClubCourseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ZoomController;
use App\Http\Controllers\ZoomCredentialController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CounselingInfoController;
use App\Http\Controllers\CounselingAppointmentController;
use App\Http\Controllers\MemberScheduleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\FlipCourseInfoController;
use App\Http\Controllers\FlipCourseCaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SlideshowController;
use App\Http\Controllers\SlideshowTypeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\VerificationController;

########## Verification API ##########

Route::prefix('verification')->group(function () {
    // Email verification
    Route::post('/email/send', [VerificationController::class, 'sendEmailCode']);
    Route::post('/email/verify', [VerificationController::class, 'verifyEmailCode']);

    // Mobile verification
    Route::post('/mobile/send', [VerificationController::class, 'sendMobileCode']);
    Route::post('/mobile/verify', [VerificationController::class, 'verifyMobileCode']);

    // Debug endpoint (only available when APP_DEBUG=true)
    Route::get('/code/{type}', [VerificationController::class, 'getCode']);
});

########## Auth API ##########

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');
});

########## Resources API ##########

Route::prefix('invitation-codes')->group(function () {
    Route::get('/', [InvitationCodeController::class, 'index']);
    Route::post('/', [InvitationCodeController::class, 'store']);
    Route::get('/{id}', [InvitationCodeController::class, 'show']);
    Route::put('/{id}', [InvitationCodeController::class, 'update']);
    Route::delete('/{id}', [InvitationCodeController::class, 'destroy']);
});

Route::prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index']);
    Route::post('/', [MemberController::class, 'store']);
    Route::get('/{id}', [MemberController::class, 'show']);
    Route::put('/{id}', [MemberController::class, 'update']);
    Route::delete('/{id}', [MemberController::class, 'destroy']);
    Route::get('/{id}/schedule', [MemberScheduleController::class, 'getSchedule']);
});

Route::prefix('notices')->group(function () {
    Route::get('/', [NoticeController::class, 'index']);
    Route::post('/', [NoticeController::class, 'store']);
    Route::get('/{id}', [NoticeController::class, 'show']);
    Route::put('/{id}', [NoticeController::class, 'update']);
    Route::delete('/{id}', [NoticeController::class, 'destroy']);
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);

    // Follower-related routes
    Route::post('/{id}/follower', [ProductController::class, 'addFollower']);
    Route::delete('/{id}/follower', [ProductController::class, 'removeFollower']);

    // Visibler-related routes
    Route::post('/{id}/visibler', [ProductController::class, 'addVisibler']);
    Route::delete('/{id}/visibler', [ProductController::class, 'removeVisibler']);
});

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}', [OrderController::class, 'update']);
    Route::delete('/{id}', [OrderController::class, 'destroy']);
});

Route::prefix('course-info-types')->group(function () {
    Route::get('/', [CourseInfoTypeController::class, 'index']);
    Route::post('/', [CourseInfoTypeController::class, 'store']);
    Route::get('/{id}', [CourseInfoTypeController::class, 'show']);
    Route::put('/{id}', [CourseInfoTypeController::class, 'update']);
    Route::delete('/{id}', [CourseInfoTypeController::class, 'destroy']);
});

Route::prefix('course-status-types')->group(function () {
    Route::get('/', [CourseStatusTypeController::class, 'index']);
    Route::post('/', [CourseStatusTypeController::class, 'store']);
    Route::get('/{id}', [CourseStatusTypeController::class, 'show']);
    Route::put('/{id}', [CourseStatusTypeController::class, 'update']);
    Route::delete('/{id}', [CourseStatusTypeController::class, 'destroy']);
});

Route::prefix('club-course-info')->group(function () {
    Route::get('/', [ClubCourseInfoController::class, 'index']);
    Route::post('/', [ClubCourseInfoController::class, 'store']);
    Route::get('/{id}', [ClubCourseInfoController::class, 'show']);
    Route::put('/{id}', [ClubCourseInfoController::class, 'update']);
    Route::delete('/{id}', [ClubCourseInfoController::class, 'destroy']);
});

Route::prefix('club-course')->group(function () {
    Route::get('/', [ClubCourseController::class, 'index']);
    Route::post('/', [ClubCourseController::class, 'store']);
    Route::get('/{id}', [ClubCourseController::class, 'show']);
    Route::put('/{id}', [ClubCourseController::class, 'update']);
    Route::delete('/{id}', [ClubCourseController::class, 'destroy']);
});

Route::prefix('slideshows')->group(function () {
    Route::get('/active', [SlideshowController::class, 'getActive']);
    Route::get('/', [SlideshowController::class, 'index']);
    Route::post('/', [SlideshowController::class, 'store']);
    Route::get('/{id}', [SlideshowController::class, 'show']);
    Route::put('/{id}', [SlideshowController::class, 'update']);
    Route::delete('/{id}', [SlideshowController::class, 'destroy']);
});

########## Types ##########

Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('/{id}', [RoleController::class, 'show']);
    Route::put('/{id}', [RoleController::class, 'update']);
    Route::delete('/{id}', [RoleController::class, 'destroy']);
});

Route::prefix('notice-types')->group(function () {
    Route::get('/', [NoticeTypeController::class, 'index']);
    Route::post('/', [NoticeTypeController::class, 'store']);
    Route::get('/{id}', [NoticeTypeController::class, 'show']);
    Route::put('/{id}', [NoticeTypeController::class, 'update']);
    Route::delete('/{id}', [NoticeTypeController::class, 'destroy']);
});

Route::prefix('level-types')->group(function () {
    Route::get('/', [LevelTypeController::class, 'index']);
    Route::post('/', [LevelTypeController::class, 'store']);
    Route::get('/{id}', [LevelTypeController::class, 'show']);
    Route::put('/{id}', [LevelTypeController::class, 'update']);
    Route::delete('/{id}', [LevelTypeController::class, 'destroy']);
});

Route::prefix('lang-types')->group(function () {
    Route::get('/', [LangTypeController::class, 'index']);
    Route::post('/', [LangTypeController::class, 'store']);
    Route::get('/{id}', [LangTypeController::class, 'show']);
    Route::put('/{id}', [LangTypeController::class, 'update']);
    Route::delete('/{id}', [LangTypeController::class, 'destroy']);
});

Route::prefix('tech-method-types')->group(function () {
    Route::get('/', [TechMethodTypeController::class, 'index']);
    Route::post('/', [TechMethodTypeController::class, 'store']);
    Route::get('/{id}', [TechMethodTypeController::class, 'show']);
    Route::put('/{id}', [TechMethodTypeController::class, 'update']);
    Route::delete('/{id}', [TechMethodTypeController::class, 'destroy']);
});

Route::prefix('slideshow-types')->group(function () {
    Route::get('/', [SlideshowTypeController::class, 'index']);
    Route::post('/', [SlideshowTypeController::class, 'store']);
    Route::get('/{id}', [SlideshowTypeController::class, 'show']);
    Route::put('/{id}', [SlideshowTypeController::class, 'update']);
    Route::delete('/{id}', [SlideshowTypeController::class, 'destroy']);
});

########## Menus ##########

Route::prefix('menus')->group(function () {
    // Special routes (BEFORE {id})
    Route::get('/active', [MenuController::class, 'getActiveMenus']);
    Route::get('/tree/all', [MenuController::class, 'getTree']);
    Route::get('/tree/role/{roleId}', [MenuController::class, 'getTreeForRole']);
    Route::put('/reorder', [MenuController::class, 'reorder']);

    // Standard CRUD
    Route::get('/', [MenuController::class, 'index']);
    Route::post('/', [MenuController::class, 'store']);
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::put('/{id}', [MenuController::class, 'update']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);

    // Role management
    Route::post('/{id}/roles', [MenuController::class, 'assignRoles']);
    Route::delete('/{id}/roles', [MenuController::class, 'removeRoles']);
});

########## Search API ##########

Route::prefix('search')->group(function () {
    // 通用搜尋
    Route::get('/', [SearchController::class, 'search']);
    
    // 全域快速搜尋
    Route::get('/global', [SearchController::class, 'globalSearch']);
    
    // 搜尋建議
    Route::get('/suggestions', [SearchController::class, 'suggestions']);
    
    // 取得可用篩選器
    Route::get('/filters', [SearchController::class, 'getAvailableFilters']);
    
    // 特定類型搜尋
    Route::get('/members', [SearchController::class, 'searchMembers']);
    Route::get('/products', [SearchController::class, 'searchProducts']);
    Route::get('/courses', [SearchController::class, 'searchCourses']);
});

########## Comment API ##########

Route::prefix('comments')->group(function () {
    // 獲取評論列表
    Route::get('/', [CommentController::class, 'index']);
    
    // 創建評論
    Route::post('/', [CommentController::class, 'store']);
    
    // 搜尋評論
    Route::get('/search', [CommentController::class, 'search']);
    
    // 獲取熱門評論
    Route::get('/trending', [CommentController::class, 'trending']);
    
    // 獲取評論統計
    Route::get('/statistics', [CommentController::class, 'statistics']);
    
    // 單個評論操作
    Route::prefix('{comment}')->group(function () {
        Route::get('/', [CommentController::class, 'show']);
        Route::put('/', [CommentController::class, 'update']);
        Route::delete('/', [CommentController::class, 'destroy']);
        
        // 互動功能
        Route::post('/like', [CommentController::class, 'toggleLike']);
        Route::post('/reaction', [CommentController::class, 'addReaction']);
        Route::post('/report', [CommentController::class, 'report']);
    });
});

########## Zoom API ##########

Route::prefix('zoom')->group(function () {
    // Zoom 憑證管理
    Route::prefix('credentials')->group(function () {
        Route::get('/', [ZoomCredentialController::class, 'index']);
        Route::post('/', [ZoomCredentialController::class, 'store']);
        Route::get('/{zoomCredential}', [ZoomCredentialController::class, 'show']);
        Route::put('/{zoomCredential}', [ZoomCredentialController::class, 'update']);
        Route::delete('/{zoomCredential}', [ZoomCredentialController::class, 'destroy']);
        Route::post('/{zoomCredential}/test', [ZoomCredentialController::class, 'testConnection']);
        Route::post('/{zoomCredential}/reset-count', [ZoomCredentialController::class, 'resetMeetingCount']);
    });
    
    // 檢查 Zoom API 連接
    Route::get('/check', [ZoomController::class, 'checkConnection']);
    
    // 為課程創建 Zoom 會議
    Route::post('/courses/{course}/meeting', [ZoomController::class, 'createMeetingForCourse']);
    
    // 獲取課程 Zoom 會議資訊
    Route::get('/courses/{course}/meeting', [ZoomController::class, 'getCourseZoomInfo']);
    
    // 刪除課程 Zoom 會議
    Route::delete('/courses/{course}/meeting', [ZoomController::class, 'deleteMeeting']);
    
    // 獲取學生加入連結
    Route::get('/courses/{course}/join', [ZoomController::class, 'getCourseJoinUrl']);
    
    // 獲取老師開始連結
    Route::get('/courses/{course}/start', [ZoomController::class, 'getHostStartUrl']);
});

########## Attendance API ##########

Route::prefix('attendance')->group(function () {
    // 取得可用的出席狀態列表
    Route::get('/statuses', [AttendanceController::class, 'getAvailableStatuses']);
    
    // 課程點名相關
    Route::prefix('courses/{course}')->group(function () {
        // 取得課程點名清單
        Route::get('/', [AttendanceController::class, 'getCourseAttendance']);
        
        // 批量點名
        Route::post('/batch', [AttendanceController::class, 'batchMarkAttendance']);
        
        // 自動生成點名清單
        Route::post('/generate-roster', [AttendanceController::class, 'generateRoster']);
        
        // 取得課程出席統計
        Route::get('/stats', [AttendanceController::class, 'getCourseAttendanceStats']);
        
        // 修改單一學生出席記錄
        Route::put('/members/{member}', [AttendanceController::class, 'updateAttendance']);
    });
    
    // 學生出席統計
    Route::get('/members/{member}/stats', [AttendanceController::class, 'getMemberAttendanceStats']);
});

########## Counseling API ##########

// 諮商服務資訊管理
Route::prefix('counseling-infos')->group(function () {
    Route::get('/', [CounselingInfoController::class, 'index']);
    Route::post('/', [CounselingInfoController::class, 'store']);
    Route::get('/{id}', [CounselingInfoController::class, 'show']);
    Route::put('/{id}', [CounselingInfoController::class, 'update']);
    Route::delete('/{id}', [CounselingInfoController::class, 'destroy']);
    
    // 諮商師管理
    Route::post('/{id}/counselors', [CounselingInfoController::class, 'assignCounselor']);
    Route::delete('/{id}/counselors', [CounselingInfoController::class, 'removeCounselor']);
});

// 諮商預約管理
Route::prefix('counseling-appointments')->group(function () {
    Route::get('/', [CounselingAppointmentController::class, 'index']);
    Route::post('/', [CounselingAppointmentController::class, 'store']);
    Route::get('/{id}', [CounselingAppointmentController::class, 'show']);
    Route::put('/{id}', [CounselingAppointmentController::class, 'update']);
    Route::delete('/{id}', [CounselingAppointmentController::class, 'destroy']);
    
    // 預約狀態管理
    Route::post('/{id}/confirm', [CounselingAppointmentController::class, 'confirm']);
    Route::post('/{id}/reject', [CounselingAppointmentController::class, 'reject']);
    Route::post('/{id}/complete', [CounselingAppointmentController::class, 'complete']);
});

########## Notification API ##########

Route::prefix('notifications')->group(function () {
    // 基本通知操作
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/{id}', [NotificationController::class, 'show']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    
    // 標記已讀功能
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/batch/read', [NotificationController::class, 'markMultipleAsRead']);
    Route::put('/all/read', [NotificationController::class, 'markAllAsRead']);
    
    // 統計資料
    Route::get('/stats/summary', [NotificationController::class, 'getStats']);
    
    // 手動觸發提醒（測試/管理用）
    Route::post('/trigger/course-reminder', [NotificationController::class, 'triggerCourseReminder']);
    Route::post('/trigger/counseling-reminder', [NotificationController::class, 'triggerCounselingReminder']);
    Route::post('/trigger/counseling-confirmation', [NotificationController::class, 'triggerCounselingConfirmation']);
    Route::post('/trigger/counseling-status-change', [NotificationController::class, 'triggerCounselingStatusChange']);
    Route::post('/trigger/counseling-time-change', [NotificationController::class, 'triggerCounselingTimeChange']);
    Route::post('/trigger/counselor-new-service', [NotificationController::class, 'triggerCounselorNewService']);
});

########## Notification Preferences API ##########

Route::prefix('notification-preferences')->group(function () {
    // 獲取和管理通知偏好
    Route::get('/', [NotificationPreferenceController::class, 'index']);
    Route::put('/{id}', [NotificationPreferenceController::class, 'update']);
    Route::put('/batch/update', [NotificationPreferenceController::class, 'batchUpdate']);
    
    // 快速設定
    Route::post('/quick-setting', [NotificationPreferenceController::class, 'quickSetting']);
    Route::post('/reset-defaults', [NotificationPreferenceController::class, 'resetToDefaults']);
    
    // 測試通知
    Route::post('/test', [NotificationPreferenceController::class, 'testNotification']);
});

########## Flip Course API ##########

// 翻轉課程模板管理
Route::prefix('flip-course-infos')->group(function () {
    Route::get('/', [FlipCourseInfoController::class, 'index']);
    Route::post('/', [FlipCourseInfoController::class, 'store']);
    Route::get('/{id}', [FlipCourseInfoController::class, 'show']);
    Route::put('/{id}', [FlipCourseInfoController::class, 'update']);
    Route::delete('/{id}', [FlipCourseInfoController::class, 'destroy']);

    // 統計資料
    Route::get('/{id}/statistics', [FlipCourseInfoController::class, 'getStatistics']);
});

// 翻轉課程案例管理與工作流
Route::prefix('flip-course-cases')->group(function () {
    // 基本 CRUD
    Route::get('/', [FlipCourseCaseController::class, 'index']);
    Route::get('/{id}', [FlipCourseCaseController::class, 'show']);

    // Phase 1: 規劃師操作
    Route::post('/{id}/confirm-payment', [FlipCourseCaseController::class, 'confirmPayment']);
    Route::post('/{id}/create-line-group', [FlipCourseCaseController::class, 'createLineGroup']);
    Route::post('/{id}/assign-counselor', [FlipCourseCaseController::class, 'assignCounselor']);
    Route::post('/{id}/assign-analyst', [FlipCourseCaseController::class, 'assignAnalyst']);

    // Phase 2: 諮商師操作
    Route::post('/{id}/schedule-counseling', [FlipCourseCaseController::class, 'scheduleCounseling']);
    Route::post('/{id}/issue-prescription', [FlipCourseCaseController::class, 'issuePrescription']);
    Route::post('/{id}/review-analysis', [FlipCourseCaseController::class, 'reviewAnalysis']);

    // Phase 3: 分析師操作
    Route::post('/{id}/create-assessment', [FlipCourseCaseController::class, 'createAssessment']);
    Route::post('/{id}/submit-analysis', [FlipCourseCaseController::class, 'submitAnalysis']);

    // 查詢相關資料
    Route::get('/{id}/prescriptions', [FlipCourseCaseController::class, 'getPrescriptions']);
    Route::get('/{id}/assessments', [FlipCourseCaseController::class, 'getAssessments']);
    Route::get('/{id}/tasks', [FlipCourseCaseController::class, 'getTasks']);
    Route::get('/{id}/notes', [FlipCourseCaseController::class, 'getNotes']);
    Route::get('/{id}/statistics', [FlipCourseCaseController::class, 'getStatistics']);
});