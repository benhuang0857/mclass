# 翻轉課程系統使用指南

## 📋 目錄
1. [系統概述](#系統概述)
2. [快速開始](#快速開始)
3. [完整工作流程](#完整工作流程)
4. [API 文檔](#api-文檔)
5. [測試流程](#測試流程)

---

## 系統概述

翻轉課程系統是一個多角色協作的學習管理系統，包含以下角色：

- **規劃師 (Planner)**: 負責訂單處理、金流確認、團隊指派
- **諮商師 (Counselor)**: 負責學習策略制定、處方簽開立
- **分析師 (Analyst)**: 負責學習成果評估、分析報告
- **學生 (Student)**: 接受課程服務

### 工作流程圖

```
購買商品 → 建立案例 → 規劃師設置 → 諮商師諮商 → 學生學習 → 分析師評估 → 循環/完成
```

---

## 快速開始

### 1. 執行資料庫遷移

```bash
php artisan migrate
```

### 2. 生成測試數據

```bash
php artisan db:seed --class=FlipCourseSeeder
```

這將建立：
- 3 個翻轉課程商品（英文初階、英文中階、普通話初階）
- 對應的翻轉課程模板
- 1 個範例案例（如果有足夠的會員資料）

### 3. 導入 Postman Collection

1. 打開 Postman
2. 導入 `MClass-FlipCourse-API.postman_collection.json`
3. 設置環境變數 `base_url` = `http://localhost:8000`（或你的 API 地址）

---

## 完整工作流程

### 階段 0: 準備商品

#### 建立翻轉課程商品
```bash
POST /api/products
```

```json
{
  "name": "翻轉英文課程 - 初階",
  "code": "FLIP-EN-BEGINNER",
  "feature_img": "https://example.com/image.jpg",
  "regular_price": 18000,
  "discount_price": 15000,
  "stock": 999,
  "status": "published",
  "limit_enrollment": false,
  "is_series": false,
  "elective": false,
  "is_visible_to_specific_students": false
}
```

#### 建立翻轉課程模板
```bash
POST /api/flip-course-infos
```

```json
{
  "product_id": 1,
  "name": "翻轉英文課程 - 初階",
  "code": "FLIP-EN-001",
  "description": "專為英文初學者設計的翻轉課程",
  "details": "完整的循環式學習系統...",
  "feature_img": "https://example.com/image.jpg",
  "teaching_mode": "hybrid",
  "status": "published",
  "lang_type_ids": [1]
}
```

---

### 階段 1: 建立訂單（自動建立案例）

規劃師幫學生建立訂單，系統會自動建立案例。

```bash
POST /api/orders
```

```json
{
  "member_id": 1,              // 學生 ID
  "code": "ORD-2025-001",
  "total": 15000,
  "currency": "TWD",
  "status": "pending",         // 金流尚未確認
  "items": [{
    "product_id": 1,
    "product_name": "翻轉英文課程 - 初階",
    "quantity": 1,
    "price": 15000,
    "options": {
      "flip_course_info_id": 1,
      "planner_id": 2          // 規劃師（自己）
    }
  }]
}
```

**後端自動處理**：
- ✅ 建立訂單
- ✅ 偵測到翻轉課程商品
- ✅ 自動建立 `flip_course_case` (workflow_stage = 'created', payment_status = 'pending')

---

### 階段 2: 規劃師操作

#### 2.1 確認金流

學生完成線下付款後，規劃師確認金流。

```bash
POST /api/flip-course-cases/1/confirm-payment
```

```json
{
  "payment_method": "bank_transfer",
  "payment_note": "學生已於 2025/01/15 完成匯款"
}
```

**系統處理**：
- payment_status → 'confirmed'
- workflow_stage → 'planning'
- order.status → 'completed'

#### 2.2 建立 Line 群組

```bash
POST /api/flip-course-cases/1/create-line-group
```

```json
{
  "line_group_url": "https://line.me/ti/g/XXXXX"
}
```

#### 2.3 指派諮商師

```bash
POST /api/flip-course-cases/1/assign-counselor
```

```json
{
  "counselor_id": 3
}
```

**系統處理**：
- 發送通知給諮商師

#### 2.4 指派分析師

```bash
POST /api/flip-course-cases/1/assign-analyst
```

```json
{
  "analyst_id": 4
}
```

**系統處理**：
- workflow_stage → 'counseling'
- 發送通知給分析師
- **啟動自動化流程**

---

### 階段 3: 諮商師操作

#### 3.1 安排諮商會議

```bash
POST /api/flip-course-cases/1/schedule-counseling
```

```json
{
  "title": "翻轉課程諮商（第 1 次循環）",
  "preferred_datetime": "2025-01-20 14:00:00",
  "confirmed_datetime": "2025-01-20 14:00:00",
  "duration": 60,
  "method": "online",
  "meeting_url": "https://zoom.us/j/123456789"
}
```

#### 3.2 開立處方簽

諮商後，根據學生情況開立學習處方。

```bash
POST /api/flip-course-cases/1/issue-prescription
```

```json
{
  "counseling_appointment_id": 1,
  "strategy_report": "根據諮商評估，學生目前處於初階程度...",
  "counseling_notes": "學生學習動機強，但缺乏練習環境",
  "learning_goals": [
    "提升英文聽力理解能力",
    "增加日常對話練習"
  ],
  "club_courses": [
    {
      "club_course_info_id": 1,
      "reason": "適合初階學員的會話課程",
      "recommended_sessions": 8
    }
  ],
  "learning_tasks": [
    {
      "title": "完成基礎單字學習",
      "description": "使用 Quizlet 學習前 500 個常用單字",
      "resources": "https://quizlet.com/xxxxx",
      "estimated_hours": 10,
      "due_date": "2025-02-15"
    }
  ]
}
```

**系統處理**：
- workflow_stage → 'analyzing'
- 發送通知給學生

---

### 階段 4: 學生執行

學生根據處方簽：
- 完成學習任務
- 參加指派的俱樂部課程

---

### 階段 5: 分析師操作

#### 5.1 建立評估

```bash
POST /api/flip-course-cases/1/create-assessment
```

```json
{
  "prescription_id": 1,
  "test_content": "英文聽力測驗 20 題",
  "test_results": {
    "listening": 75,
    "speaking": 60,
    "vocabulary": 80
  },
  "test_score": 72
}
```

#### 5.2 提交分析報告

```bash
POST /api/flip-course-cases/1/submit-analysis
```

```json
{
  "assessment_id": 1,
  "analysis_report": "學生在本次循環中表現良好，完成了 80% 的學習任務...",
  "metrics": {
    "improvement_rate": 25,
    "task_completion_rate": 80
  },
  "recommendations": [
    "增加口說練習時間",
    "參加更多會話課程"
  ],
  "study_hours": 35,
  "tasks_completed": 8,
  "courses_attended": 8
}
```

**系統處理**：
- workflow_stage → 'cycling'
- 發送通知給諮商師審查

---

### 階段 6: 諮商師審查與決策

諮商師審查分析報告，決定下一步。

```bash
POST /api/flip-course-cases/1/review-analysis
```

#### 選項 A: 繼續循環

```json
{
  "assessment_id": 1,
  "continue_cycle": true,
  "review_notes": "學生進步明顯，但仍需加強口說，建議進入下一循環"
}
```

**系統處理**：
- cycle_count +1
- workflow_stage → 'counseling'
- 回到階段 3（諮商師重新安排諮商）

#### 選項 B: 完成案例

```json
{
  "assessment_id": 1,
  "continue_cycle": false,
  "review_notes": "學生已達成學習目標，課程結束"
}
```

**系統處理**：
- workflow_stage → 'completed'
- completed_at → 當前時間
- 發送完成通知給學生

---

## API 文檔

### FlipCourseInfo APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/flip-course-infos` | 取得所有課程模板 |
| POST | `/api/flip-course-infos` | 建立課程模板 |
| GET | `/api/flip-course-infos/{id}` | 取得課程模板詳情 |
| PUT | `/api/flip-course-infos/{id}` | 更新課程模板 |
| DELETE | `/api/flip-course-infos/{id}` | 刪除課程模板 |
| GET | `/api/flip-course-infos/{id}/statistics` | 取得課程統計 |

### FlipCourseCase APIs

#### 基本查詢
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/flip-course-cases` | 取得所有案例 |
| GET | `/api/flip-course-cases/{id}` | 取得案例詳情 |

#### 規劃師操作
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/flip-course-cases/{id}/confirm-payment` | 確認金流 |
| POST | `/api/flip-course-cases/{id}/create-line-group` | 建立 Line 群組 |
| POST | `/api/flip-course-cases/{id}/assign-counselor` | 指派諮商師 |
| POST | `/api/flip-course-cases/{id}/assign-analyst` | 指派分析師 |

#### 諮商師操作
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/flip-course-cases/{id}/schedule-counseling` | 安排諮商會議 |
| POST | `/api/flip-course-cases/{id}/issue-prescription` | 開立處方簽 |
| POST | `/api/flip-course-cases/{id}/review-analysis` | 審查分析報告 |

#### 分析師操作
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/flip-course-cases/{id}/create-assessment` | 建立評估 |
| POST | `/api/flip-course-cases/{id}/submit-analysis` | 提交分析報告 |

#### 查詢相關資料
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/flip-course-cases/{id}/prescriptions` | 取得處方簽列表 |
| GET | `/api/flip-course-cases/{id}/assessments` | 取得評估列表 |
| GET | `/api/flip-course-cases/{id}/tasks` | 取得任務列表 |
| GET | `/api/flip-course-cases/{id}/notes` | 取得備註列表 |
| GET | `/api/flip-course-cases/{id}/statistics` | 取得案例統計 |

---

## 測試流程

### 方法 1: 使用 Postman Collection

1. 導入 `MClass-FlipCourse-API.postman_collection.json`
2. 按照資料夾順序執行：
   - 0. Setup - 建立商品與課程模板
   - 1. Create Order - 建立訂單（自動建立案例）
   - 2. Planner Phase - 規劃師操作
   - 3. Counselor Phase - 諮商師操作
   - 4. Analyst Phase - 分析師操作
   - 5. Query APIs - 查詢各種資料

### 方法 2: 使用測試數據

```bash
# 重置資料庫並重新生成測試數據
php artisan migrate:fresh --seed

# 查看生成的測試數據
php artisan tinker
>>> App\Models\FlipCourseInfo::with('product')->get()
>>> App\Models\FlipCourseCase::with('student', 'planner')->get()
```

---

## 常見查詢

### 查詢規劃師的所有案例

```bash
GET /api/flip-course-cases?planner_id=2
```

### 查詢諮商師待處理的案例

```bash
GET /api/flip-course-cases?counselor_id=3&workflow_stage=counseling
```

### 查詢分析師待處理的案例

```bash
GET /api/flip-course-cases?analyst_id=4&workflow_stage=analyzing
```

### 查詢學生的案例

```bash
GET /api/flip-course-cases?student_id=1
```

---

## 資料結構說明

### workflow_stage 狀態

- `created`: 剛建立，等待金流確認
- `planning`: 規劃中，等待指派團隊
- `counseling`: 諮商中，等待諮商師處理
- `analyzing`: 分析中，等待分析師評估
- `cycling`: 循環中，等待諮商師審查
- `completed`: 已完成
- `cancelled`: 已取消

### payment_status 狀態

- `pending`: 等待付款
- `confirmed`: 已確認
- `failed`: 失敗

---

## 通知系統

系統會自動發送以下通知：

- `flip_case_assigned`: 案例被指派（發給諮商師/分析師）
- `flip_task_assigned`: 任務被指派
- `flip_prescription_issued`: 處方簽已開立（發給學生）
- `flip_analysis_completed`: 分析報告已完成（發給諮商師）
- `flip_cycle_started`: 新循環開始（發給諮商師）
- `flip_case_completed`: 案例已完成（發給學生）

---

## 支援與回饋

如有問題或建議，請聯繫開發團隊。
