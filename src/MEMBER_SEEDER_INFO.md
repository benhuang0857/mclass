# 會員測試資料說明

## 🎯 執行 Seeder

```bash
# 完整重置並建立所有測試資料
php artisan migrate:fresh --seed

# 或只執行會員 Seeder
php artisan db:seed --class=MemberSeeder
```

---

## 👥 建立的會員列表

### 🎓 學生 (Student) - 4 位

| 姓名 | 帳號 | Email | 密碼 |
|------|------|-------|------|
| 王小明 | student001 | student001@example.com | password |
| John Smith | student002 | student002@example.com | password |
| 李美華 | student003 | student003@example.com | password |
| 陳志豪 | student004 | student004@example.com | password |

**角色**: `student`

---

### 📋 規劃師 (Planner) - 2 位

| 姓名 | 帳號 | Email | 密碼 |
|------|------|-------|------|
| 張規劃 | planner001 | planner001@example.com | password |
| Emily Chen | planner002 | planner002@example.com | password |

**角色**: `planner`

**職責**:
- 處理訂單
- 確認金流
- 建立 Line 群組
- 指派諮商師和分析師

---

### 💬 諮商師 (Counselor) - 3 位

| 姓名 | 帳號 | Email | 密碼 |
|------|------|-------|------|
| 林諮商 | counselor001 | counselor001@example.com | password |
| David Lee | counselor002 | counselor002@example.com | password |
| 黃心理 | counselor003 | counselor003@example.com | password |

**角色**: `counselor`

**職責**:
- 安排諮商會議
- 制定學習策略
- 開立處方簽（任務 + 課程）
- 審查分析報告
- 決定是否進入下一循環

---

### 📊 分析師 (Analyst) - 2 位

| 姓名 | 帳號 | Email | 密碼 |
|------|------|-------|------|
| 吳分析 | analyst001 | analyst001@example.com | password |
| Sarah Wang | analyst002 | analyst002@example.com | password |

**角色**: `analyst`

**職責**:
- 建立測驗/評估
- 分析學習成果
- 提交分析報告

---

### 👨‍🏫 教師 (Teacher) - 2 位

| 姓名 | 帳號 | Email | 密碼 |
|------|------|-------|------|
| 鄭老師 | teacher001 | teacher001@example.com | password |
| Michael Johnson | teacher002 | teacher002@example.com | password |

**角色**: `teacher`

**職責**:
- 教授俱樂部課程
- 管理課堂出席

---

### 💼 業務 (Sales) - 1 位

| 姓名 | 帳號 | Email | 密碼 |
|------|------|-------|------|
| 許業務 | sales001 | sales001@example.com | password |

**角色**: `sales`

**職責**:
- 銷售課程商品

---

## 🔐 登入資訊

所有測試帳號的密碼都是: **`password`**

---

## 📝 測試流程範例

### 建立翻轉課程案例

使用以下帳號進行測試：

1. **規劃師** (`planner001@example.com`) 建立訂單
   ```json
   POST /api/orders
   {
     "member_id": 1,  // 學生 student001
     "items": [{
       "product_id": 1,
       "options": {
         "planner_id": 5  // 規劃師 planner001
       }
     }]
   }
   ```

2. **規劃師** 確認金流、建立 Line 群組、指派團隊
   - 指派諮商師: `counselor001` (ID 7)
   - 指派分析師: `analyst001` (ID 10)

3. **諮商師** (`counselor001@example.com`) 安排諮商、開立處方簽

4. **分析師** (`analyst001@example.com`) 建立評估、提交分析

5. **諮商師** 審查分析報告、決定下一步

---

## 🔍 查詢會員角色

```bash
# 查詢所有規劃師
GET /api/members?role=planner

# 查詢所有諮商師
GET /api/members?role=counselor

# 查詢所有分析師
GET /api/members?role=analyst
```

---

## 🗄️ 資料庫查詢

```sql
-- 查看所有會員及其角色
SELECT m.id, m.nickname, m.email, r.name as role
FROM members m
LEFT JOIN member_role mr ON m.id = mr.member_id
LEFT JOIN roles r ON mr.role_id = r.id
ORDER BY r.sort, m.id;

-- 查看規劃師
SELECT m.* FROM members m
JOIN member_role mr ON m.id = mr.member_id
JOIN roles r ON mr.role_id = r.id
WHERE r.slug = 'planner';

-- 查看諮商師
SELECT m.* FROM members m
JOIN member_role mr ON m.id = mr.member_id
JOIN roles r ON mr.role_id = r.id
WHERE r.slug = 'counselor';

-- 查看分析師
SELECT m.* FROM members m
JOIN member_role mr ON m.id = mr.member_id
JOIN roles r ON mr.role_id = r.id
WHERE r.slug = 'analyst';
```

---

## ⚠️ 注意事項

1. **密碼安全**: 所有測試帳號使用相同密碼 `password`，**僅供測試環境使用**
2. **角色分配**: 每個會員只分配一個角色，實際環境可能需要多角色支援
3. **資料重置**: 執行 `migrate:fresh --seed` 會刪除所有現有資料

---

## 📧 會員帳號總覽

總共建立 **13 位會員**：
- 學生: 4 位
- 規劃師: 2 位
- 諮商師: 3 位
- 分析師: 2 位
- 教師: 2 位
- 業務: 1 位
