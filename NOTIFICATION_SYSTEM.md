# 提醒系統使用指南

## 🎯 系統概述

提醒系統的第一階段已完成實作，主要功能包括：

- **課程開始提醒**: 購買課程的用戶會在課程開始前收到提醒
- **諮商預約提醒**: 學生和諮商師會在預約開始前收到提醒
- **課程變更通知**: 課程時間、地點變更時通知相關用戶
- **通知管理**: 用戶可以查看、標記已讀、刪除通知

## 🏗️ 架構組件

### 數據表
- `notifications` - 儲存所有通知記錄

### 模型
- `Notification` - 通知模型，包含狀態管理方法

### 服務
- `NotificationService` - 核心通知服務，處理通知創建和發送邏輯

### 控制器
- `NotificationController` - API 控制器，提供通知的 CRUD 操作

### 命令
- `SendNotifications` - 發送待發送的通知
- `GenerateCourseReminders` - 生成課程提醒通知
- `GenerateCounselingReminders` - 生成諮商提醒通知

## 🚀 使用方式

### 1. 初始化

首先運行 migration 來創建通知表：

```bash
php artisan migrate
```

### 2. API 端點使用

#### 獲取用戶通知
```bash
GET /api/notifications?member_id=123&unread_only=true&limit=10
```

#### 標記通知為已讀
```bash
PUT /api/notifications/456/read
```

#### 獲取通知統計
```bash
GET /api/notifications/stats/summary?member_id=123
```

#### 手動觸發課程提醒（測試用）
```bash
POST /api/notifications/trigger/course-reminder
{
    "course_id": 123,
    "minutes_before": 60
}
```

### 3. 命令行使用

#### 手動生成課程提醒
```bash
# 查看即將開始的課程（不實際創建通知）
php artisan notifications:generate-course-reminders --dry-run

# 為24小時內的課程創建1小時前提醒
php artisan notifications:generate-course-reminders --hours=24 --minutes-before=60

# 為所有確認的諮商預約創建提醒
php artisan notifications:generate-counseling-reminders
```

#### 發送待發送通知
```bash
# 查看有多少待發送通知
php artisan notifications:send --dry-run

# 實際發送通知
php artisan notifications:send
```

## ⏰ 自動排程

系統已設定以下自動排程（在 `routes/console.php`）：

- **每分鐘**: 發送待發送的通知
- **每5分鐘**: 生成1小時前的課程和諮商提醒
- **每天上午9點**: 生成24小時前的課程和諮商提醒

要啟用自動排程，需在伺服器上設定 cron job：

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📝 通知類型

目前支援的通知類型：

### 課程相關
1. `course_reminder` - 課程開始提醒
2. `course_change` - 課程變更通知
3. `course_new_class` - 課程新班開設通知
4. `course_price_change` - 課程價格變動通知
5. `course_status_change` - 課程狀態變更通知
6. `course_registration_deadline` - 課程報名截止提醒

### 諮商相關  
7. `counseling_reminder` - 諮商預約提醒
8. `counseling_confirmed` - 諮商預約確認通知
9. `counseling_status_change` - 諮商預約狀態變更通知
10. `counseling_time_change` - 諮商時間變更通知

### 諮商師相關
11. `counselor_new_service` - 諮商師新服務通知
12. `counselor_specific` - 指定諮商師相關通知

## 🎨 前端整合範例

### JavaScript 取得通知列表
```javascript
async function fetchNotifications(memberId, unreadOnly = false) {
    const response = await fetch(`/api/notifications?member_id=${memberId}&unread_only=${unreadOnly}`);
    const data = await response.json();
    
    console.log(`未讀通知數量: ${data.stats.unread_count}`);
    data.notifications.forEach(notification => {
        console.log(`${notification.title}: ${notification.content}`);
    });
}

// 標記通知已讀
async function markAsRead(notificationId) {
    await fetch(`/api/notifications/${notificationId}/read`, {
        method: 'PUT'
    });
}
```

## 🔧 自定義與擴展

### 新增通知類型
1. 在 Migration 的 `type` enum 中新增類型
2. 在 `NotificationService` 中新增對應的創建方法
3. 在需要的地方調用新的通知創建方法

### 整合其他發送方式
在 `NotificationService::sendNotification()` 方法中可以整合：
- Email 發送
- Push Notification
- SMS 發送
- Webhook 通知

### 新增觸發條件
可以在以下地方新增通知觸發：
- Model 的 Observer
- Controller 的相關操作後
- Event Listener 中
- 定時任務中

## 📊 監控與維護

### 查看通知發送狀況
```sql
-- 查看未讀通知統計
SELECT type, COUNT(*) as count 
FROM notifications 
WHERE is_read = false 
GROUP BY type;

-- 查看發送失敗的通知
SELECT * FROM notifications 
WHERE scheduled_at <= NOW() 
AND sent_at IS NULL;
```

### 清理舊通知
建議定期清理超過30天的已讀通知：

```sql
DELETE FROM notifications 
WHERE is_read = true 
AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## ✅ 第二階段功能（已完成）

第二階段已實作完成：
- ✅ 諮商預約確認通知
- ✅ 諮商狀態變更通知
- ✅ 諮商時間變更通知
- ✅ 諮商師新服務通知
- ✅ 自動整合到現有控制器

## ✅ 第三階段功能（已完成）

第三階段已實作完成：
- ✅ 課程追蹤者通知系統
- ✅ 用戶通知偏好設定
- ✅ 課程新班開設通知
- ✅ 課程價格變動通知
- ✅ 課程狀態變更通知
- ✅ 報名截止提醒通知
- ✅ 靜音時間和個人化設定
- ✅ 通知偏好管理 API

## 🚧 未來功能規劃

第四階段將包括：
- Email/Push 通知整合
- 通知模板系統
- 通知統計分析
- 批量通知管理
- 通知歷史記錄分析

## 🐛 故障排除

### 通知沒有自動發送
1. 檢查 cron job 是否正確設定
2. 檢查 Laravel 排程是否正常運行: `php artisan schedule:list`
3. 手動運行發送命令: `php artisan notifications:send`

### 通知重複發送
檢查 `whereDoesntHave` 查詢是否正確過濾已存在的通知。

### 效能問題
- 為 `notifications` 表加上適當的索引
- 考慮使用 Queue 來處理大量通知發送
- 定期清理舊通知記錄

## 🆕 第二階段新增 API 端點

### 諮商相關通知 API

#### 手動觸發諮商確認通知（測試用）
```bash
POST /api/notifications/trigger/counseling-confirmation
{
    "appointment_id": 123
}
```

#### 手動觸發諮商狀態變更通知（測試用）
```bash
POST /api/notifications/trigger/counseling-status-change
{
    "appointment_id": 123,
    "old_status": "pending",
    "new_status": "confirmed"
}
```

#### 手動觸發諮商時間變更通知（測試用）
```bash
POST /api/notifications/trigger/counseling-time-change
{
    "appointment_id": 123,
    "old_time": "2024-01-01 10:00:00",
    "new_time": "2024-01-01 14:00:00"
}
```

#### 手動觸發諮商師新服務通知（測試用）
```bash
POST /api/notifications/trigger/counselor-new-service
{
    "counselor_id": 456,
    "counseling_info_id": 789
}
```

## 📋 自動觸發場景

### 諮商系統整合

系統已自動整合到現有的諮商控制器：

1. **諮商預約確認** (`CounselingAppointmentController::confirm`)
   - 自動發送確認通知給學生和諮商師
   - 自動創建1小時前的提醒通知

2. **諮商預約更新** (`CounselingAppointmentController::update`)
   - 狀態變更時自動通知相關用戶
   - 時間變更時自動通知雙方

3. **諮商師指派** (`CounselingInfoController::assignCounselor`)
   - 新諮商師指派到服務時，通知曾經預約該諮商師的用戶

## 🆕 第三階段新功能

### 通知偏好設定 API

#### 獲取用戶通知偏好
```bash
GET /api/notification-preferences?member_id=123
```

#### 批量更新偏好設定
```bash
PUT /api/notification-preferences/batch/update
{
    "member_id": 123,
    "preferences": [
        {
            "notification_type": "course_reminder",
            "enabled": true,
            "delivery_methods": ["database", "email"],
            "advance_minutes": 120,
            "schedule_settings": {
                "quiet_hours": {
                    "start": "22:00",
                    "end": "08:00"
                }
            }
        }
    ]
}
```

#### 快速設定（全部啟用/停用）
```bash
POST /api/notification-preferences/quick-setting
{
    "member_id": 123,
    "action": "enable_all" // 或 "disable_all", "enable_important_only"
}
```

#### 重設為預設設定
```bash
POST /api/notification-preferences/reset-defaults
{
    "member_id": 123
}
```

#### 測試通知設定
```bash
POST /api/notification-preferences/test
{
    "member_id": 123,
    "notification_type": "course_reminder"
}
```

### 課程追蹤者通知自動觸發

系統已自動整合到現有控制器：

4. **課程創建** (`ClubCourseController::store`)
   - 自動通知追蹤者有新班次開設
   - 自動創建報名截止提醒

5. **課程更新** (`ClubCourseController::update`)
   - 時間/地點變更自動通知

6. **商品更新** (`ProductController::update`)
   - 價格變動自動通知追蹤者
   - 狀態變更（如重新開放）自動通知

### 智能通知特性

- **用戶偏好尊重**: 所有通知都會檢查用戶偏好設定
- **靜音時間**: 支援設定靜音時間，避開打擾
- **個人化提前時間**: 每種通知類型都可設定個人化提前時間
- **多元推送方式**: 支援系統內、Email、推播、簡訊多種方式