# 翻轉課程與諮商系統整合說明

## 📋 整合概述

翻轉課程系統現在完全整合了現有的諮商系統（`counseling_appointments`），使得翻轉課程的諮商會議可以複用所有現有的諮商功能。

---

## 🔄 整合架構

```
一般諮商服務                翻轉課程諮商
     ↓                          ↓
┌──────────────────────────────────────────┐
│      counseling_appointments             │
│  ├─ order_item_id (一般諮商)             │
│  └─ flip_course_case_id (翻轉課程諮商)   │
└──────────────────────────────────────────┘
              ↓
         prescriptions (處方簽)
```

### 資料關聯

```
flip_course_cases (翻轉課程案例)
  ├─ counselingAppointments (諮商會議) - 多個
  └─ prescriptions (處方簽) - 多個
       └─ counselingAppointment (關聯的諮商會議) - 一個

counseling_appointments (諮商會議)
  ├─ order_item_id (一般諮商)
  ├─ flip_course_case_id (翻轉課程諮商)
  └─ prescription (處方簽) - 一個
```

---

## 📊 資料表變更

### 新增欄位

#### `counseling_appointments` 表
```sql
ALTER TABLE counseling_appointments
ADD COLUMN flip_course_case_id BIGINT UNSIGNED NULL
AFTER order_item_id;

-- order_item_id 改為可選（因為翻轉課程不透過一般訂單購買）
ALTER TABLE counseling_appointments
MODIFY COLUMN order_item_id BIGINT UNSIGNED NULL;
```

#### `prescriptions` 表
```sql
ALTER TABLE prescriptions
ADD COLUMN counseling_appointment_id BIGINT UNSIGNED NULL
AFTER counselor_id;
```

---

## 💻 使用範例

### 1. 諮商師預約諮商會議

```php
use App\Services\FlipCourseWorkflowService;

$workflowService = new FlipCourseWorkflowService();
$case = FlipCourseCase::find(1);

// 預約諮商會議
$appointment = $workflowService->scheduleCounselingMeeting($case, [
    'preferred_datetime' => '2025-11-15 14:00:00',
    'duration' => 60,
    'method' => 'online',
    'meeting_url' => 'https://zoom.us/j/xxx',
    'title' => '第一次諮商：了解學習需求',
    'description' => '與學生討論學習目標和困難',
]);

echo "諮商會議已預約：{$appointment->title}";
echo "會議時間：{$appointment->confirmed_datetime}";
echo "Zoom 連結：{$appointment->meeting_url}";
```

### 2. 諮商後建立學習策略

```php
// 取得諮商會議
$appointment = CounselingAppointment::find(1);

// 基於諮商會議建立處方簽
$prescription = $workflowService->createStrategy($case, [
    'strategy_report' => '根據諮商結果，學生需要加強...',
    'counseling_notes' => '學生表示對文法感到困難...',
    'learning_goals' => ['提升文法理解', '增強聽力能力'],
], $appointment);

// 諮商會議會自動標記為完成
echo "處方簽已建立，關聯諮商會議 #{$appointment->id}";
```

### 3. 查詢諮商會議

```php
// 查詢案例的所有諮商會議
$counselingMeetings = $case->counselingAppointments()
    ->with(['counselor', 'prescription'])
    ->orderBy('confirmed_datetime', 'desc')
    ->get();

foreach ($counselingMeetings as $meeting) {
    echo "會議時間：{$meeting->confirmed_datetime}\n";
    echo "諮商師：{$meeting->counselor->name}\n";
    echo "會議連結：{$meeting->meeting_url}\n";

    if ($meeting->prescription) {
        echo "已開立處方簽 #{$meeting->prescription->id}\n";
    }
}
```

### 4. 區分一般諮商和翻轉課程諮商

```php
// 查詢所有翻轉課程諮商
$flipCourseCounselings = CounselingAppointment::flipCourse()
    ->with(['flipCourseCase', 'student', 'counselor'])
    ->get();

// 查詢所有一般諮商
$regularCounselings = CounselingAppointment::regular()
    ->with(['orderItem', 'counselingInfo'])
    ->get();

// 檢查單一會議類型
if ($appointment->isFlipCourseCounseling()) {
    echo "這是翻轉課程的諮商";
    $case = $appointment->flipCourseCase;
} elseif ($appointment->isRegularCounseling()) {
    echo "這是一般諮商服務";
    $orderItem = $appointment->orderItem;
}
```

### 5. 諮商師統一查看所有諮商會議

```php
// 諮商師可以看到所有類型的諮商會議
$counselorId = 5;

$allMeetings = CounselingAppointment::where('counselor_id', $counselorId)
    ->with(['student', 'flipCourseCase', 'orderItem'])
    ->orderBy('confirmed_datetime')
    ->get();

foreach ($allMeetings as $meeting) {
    if ($meeting->isFlipCourseCounseling()) {
        echo "[翻轉課程] {$meeting->title} - 案例 #{$meeting->flip_course_case_id}\n";
    } else {
        echo "[一般諮商] {$meeting->title} - 服務：{$meeting->counselingInfo->name}\n";
    }
}
```

---

## 🎯 工作流程範例

### 完整的翻轉課程諮商流程

```php
// 1. 建立案例
$case = $workflowService->createCase($flipCourseInfo, $student, $planner);

// 2. 指派諮商師和分析師
$workflowService->assignCounselor($case, $counselor);
$workflowService->assignAnalyst($case, $analyst);

// 3. 諮商師預約諮商會議
$appointment = $workflowService->scheduleCounselingMeeting($case, [
    'preferred_datetime' => '2025-11-15 14:00:00',
    'duration' => 60,
    'method' => 'online',
    'meeting_url' => 'https://zoom.us/j/123456789',
]);
// 👉 系統自動發送諮商確認通知給學生和諮商師

// 4. 諮商會議進行中...
// （諮商師和學生在 Zoom 上進行會議）

// 5. 諮商後，諮商師建立學習策略
$prescription = $workflowService->createStrategy($case, [
    'strategy_report' => '學生需要加強文法基礎...',
    'counseling_notes' => '會議中討論了學習困難...',
    'learning_goals' => ['提升文法', '增強聽力'],
], $appointment);
// 👉 諮商會議自動標記為完成

// 6. 開立處方簽（派課程和學習任務）
$workflowService->issuePrescription(
    $prescription,
    clubCourseIds: [
        ['id' => 1, 'reason' => '加強文法', 'recommended_sessions' => 10],
    ],
    learningTasks: [
        [
            'title' => '每日文法練習',
            'description' => '完成文法練習題',
            'due_date' => now()->addWeeks(2),
        ],
    ]
);
// 👉 系統發送處方簽通知給分析師和學生

// 7-10. 後續的分析和循環流程...
```

---

## 📈 優勢

### ✅ 整合後的好處

1. **統一的諮商管理**
   - 諮商師在同一個地方查看所有諮商會議
   - 不需要在多個系統間切換

2. **完整的會議記錄**
   - 時間、地點、Zoom 連結
   - 諮商備註、學生反饋
   - 會議評分

3. **複用現有功能**
   - Zoom 整合
   - 諮商提醒通知
   - 諮商變更通知

4. **清晰的追溯性**
   - 每個處方簽可以追溯到具體的諮商會議
   - 完整的學習輔導歷史記錄

5. **靈活性**
   - 一般諮商和翻轉課程諮商可以共存
   - 未來可以輕鬆擴展其他類型的諮商

---

## 📊 查詢範例

### 統計查詢

```php
// 案例的諮商統計
$stats = [
    'total_counseling_sessions' => $case->counselingAppointments()->count(),
    'completed_sessions' => $case->counselingAppointments()
        ->where('status', 'completed')->count(),
    'average_duration' => $case->counselingAppointments()
        ->avg('duration'),
    'average_rating' => $case->counselingAppointments()
        ->whereNotNull('rating')->avg('rating'),
];

// 諮商師的工作統計
$counselorStats = [
    'flip_course_sessions' => CounselingAppointment::flipCourse()
        ->where('counselor_id', $counselorId)->count(),
    'regular_sessions' => CounselingAppointment::regular()
        ->where('counselor_id', $counselorId)->count(),
    'this_week_sessions' => CounselingAppointment::where('counselor_id', $counselorId)
        ->whereBetween('confirmed_datetime', [now()->startOfWeek(), now()->endOfWeek()])
        ->count(),
];
```

### 處方簽關聯查詢

```php
// 查詢處方簽及其諮商會議
$prescription = Prescription::with([
    'counselingAppointment' => function($query) {
        $query->with(['student', 'counselor']);
    }
])->find(1);

if ($prescription->counselingAppointment) {
    echo "此處方簽基於諮商會議：\n";
    echo "時間：{$prescription->counselingAppointment->confirmed_datetime}\n";
    echo "時長：{$prescription->counselingAppointment->duration} 分鐘\n";
    echo "會議備註：{$prescription->counselingAppointment->counselor_notes}\n";
}
```

---

## 🔧 API 端點建議

整合後，可以新增以下 API 端點：

```php
// 翻轉課程諮商相關
POST   /api/flip-course-cases/{id}/counseling-appointments     // 預約諮商
GET    /api/flip-course-cases/{id}/counseling-appointments     // 查詢案例的諮商會議
PATCH  /api/counseling-appointments/{id}                       // 更新會議資訊

// 諮商師工作台
GET    /api/counselors/{id}/appointments                        // 所有諮商會議（一般+翻轉）
GET    /api/counselors/{id}/flip-course-cases                  // 諮商師的翻轉課程案例
```

---

## ⚠️ 注意事項

1. **必填欄位**
   - `order_item_id` 和 `flip_course_case_id` 至少要有一個
   - 一般諮商必須有 `order_item_id`
   - 翻轉課程諮商必須有 `flip_course_case_id`

2. **通知整合**
   - 現有的諮商通知（`counseling_reminder`, `counseling_confirmed` 等）對兩種類型都生效
   - 翻轉課程還有額外的通知（`flip_prescription_issued` 等）

3. **權限控制**
   - 確保 API 層面檢查用戶只能查看/修改自己相關的諮商會議
   - 諮商師可以查看所有自己的諮商（不管類型）

---

## ✅ Migration 執行

執行新的 migration：

```bash
php artisan migrate
```

這會：
1. 在 `counseling_appointments` 新增 `flip_course_case_id` 欄位
2. 在 `prescriptions` 新增 `counseling_appointment_id` 欄位
3. `order_item_id` 改為可選

---

## 📝 總結

整合完成後，翻轉課程的諮商流程將更加完整：

**之前**：只有處方簽結果，缺少諮商過程記錄

**現在**：
- ✅ 完整的諮商會議記錄（時間、地點、連結、備註）
- ✅ 諮商會議與處方簽的關聯
- ✅ 統一的諮商管理介面
- ✅ 複用現有的 Zoom 整合和通知功能
- ✅ 清晰的學習輔導歷史追溯

這樣的設計既保持了系統的靈活性，又避免了重複開發！🎉
