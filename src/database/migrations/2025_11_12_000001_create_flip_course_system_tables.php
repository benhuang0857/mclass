<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==========================================
        // 1. 課程模板層 (Template Layer)
        // ==========================================

        Schema::create('flip_course_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('對應的商品 ID');
            $table->string('name')->comment('課程名稱');
            $table->string('code')->unique()->comment('課程代碼');
            $table->text('description')->comment('課程簡介/目標');
            $table->text('details')->comment('課程介紹與規劃');
            $table->string('feature_img')->comment('主視覺圖片 (16:9 比例)');
            $table->enum('teaching_mode', ['online', 'offline', 'hybrid'])->comment('上課方式: 線上/實體/混合');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->comment('課程狀態');

            // 建立者追蹤
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');
            $table->foreignId('updated_by')->nullable()->constrained('users')->comment('最後更新者');

            $table->timestamps();
        });

        // 語言分類 (多對多)
        Schema::create('lang_type_flip_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lang_type_id')->constrained('lang_types')->onDelete('cascade')->comment('語言類型');
            $table->foreignId('flip_course_info_id')->constrained('flip_course_infos')->onDelete('cascade')->comment('翻轉課程');
            $table->timestamps();

            $table->unique(['lang_type_id', 'flip_course_info_id'], 'unique_lang_flip_course');
        });

        // ==========================================
        // 2. 案例層 (Case Layer)
        // ==========================================

        Schema::create('flip_course_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flip_course_info_id')->constrained('flip_course_infos')->onDelete('cascade')->comment('翻轉課程模板');
            $table->foreignId('student_id')->constrained('members')->onDelete('cascade')->comment('學生');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null')->comment('訂單');

            // 團隊成員
            $table->foreignId('planner_id')->nullable()->constrained('members')->onDelete('set null')->comment('規劃師');
            $table->foreignId('counselor_id')->nullable()->constrained('members')->onDelete('set null')->comment('諮商師');
            $table->foreignId('analyst_id')->nullable()->constrained('members')->onDelete('set null')->comment('分析師');

            // 工作流狀態
            $table->enum('workflow_stage', [
                'created',      // 剛建立
                'planning',     // 規劃中
                'counseling',   // 諮商中
                'analyzing',    // 分析中
                'cycling',      // 循環中
                'completed',    // 已完成
                'cancelled'     // 已取消
            ])->default('created')->comment('工作流階段');

            // 循環追蹤
            $table->integer('cycle_count')->default(0)->comment('循環次數');

            // 規劃師的工作內容
            $table->string('line_group_url')->nullable()->comment('Line 群組連結');
            $table->enum('payment_status', ['pending', 'confirmed', 'failed'])->default('pending')->comment('金流狀態');
            $table->timestamp('payment_confirmed_at')->nullable()->comment('金流確認時間');

            // 時間追蹤
            $table->timestamp('started_at')->nullable()->comment('開始時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');
            $table->timestamps();

            $table->index(['student_id', 'workflow_stage']);
            $table->index(['counselor_id', 'workflow_stage']);
            $table->index(['analyst_id', 'workflow_stage']);
        });

        // ==========================================
        // 3. 處方簽層 (Prescription Layer)
        // ==========================================

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flip_course_case_id')->constrained('flip_course_cases')->onDelete('cascade')->comment('翻轉課程案例');
            $table->foreignId('counselor_id')->constrained('members')->onDelete('cascade')->comment('開立處方的諮商師');
            $table->foreignId('counseling_appointment_id')->nullable()->constrained('counseling_appointments')->onDelete('set null')->comment('關聯的諮商會議');
            $table->integer('cycle_number')->comment('第幾次循環');

            // 處方內容
            $table->text('strategy_report')->comment('學習策略報告');
            $table->text('counseling_notes')->nullable()->comment('諮商筆記');
            $table->json('learning_goals')->nullable()->comment('學習目標 (JSON)');

            // 狀態
            $table->enum('status', ['draft', 'issued', 'completed', 'cancelled'])->default('draft')->comment('處方簽狀態');
            $table->timestamp('issued_at')->nullable()->comment('開立時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');

            $table->timestamps();

            $table->index(['flip_course_case_id', 'cycle_number']);
        });

        // 處方簽 - 課程關聯 (派課程給學生)
        Schema::create('prescription_club_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade')->comment('處方簽');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade')->comment('俱樂部課程');
            $table->text('reason')->nullable()->comment('指派原因/說明');
            $table->integer('recommended_sessions')->default(1)->comment('建議參加堂數');
            $table->timestamps();
        });

        // 學習任務 (處方簽包含的任務)
        Schema::create('learning_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade')->comment('處方簽');
            $table->string('title')->comment('任務標題');
            $table->text('description')->comment('任務描述');
            $table->text('resources')->nullable()->comment('相關資源/連結');
            $table->integer('estimated_hours')->nullable()->comment('預估學習時數');

            // 狀態追蹤
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending')->comment('任務狀態');
            $table->integer('progress')->default(0)->comment('進度百分比 (0-100)');

            // 時間管理
            $table->timestamp('due_date')->nullable()->comment('截止日期');
            $table->timestamp('started_at')->nullable()->comment('開始時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');

            $table->timestamps();

            $table->index(['prescription_id', 'status']);
        });

        // ==========================================
        // 4. 測驗/分析層 (Assessment Layer)
        // ==========================================

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade')->comment('對應的處方簽');
            $table->foreignId('analyst_id')->constrained('members')->onDelete('cascade')->comment('負責的分析師');

            // 測驗內容
            $table->text('test_content')->nullable()->comment('測驗內容/題目');
            $table->json('test_results')->nullable()->comment('測驗結果 (JSON)');
            $table->integer('test_score')->nullable()->comment('測驗分數');

            // 分析報告
            $table->text('analysis_report')->comment('分析報告');
            $table->json('metrics')->nullable()->comment('學習指標 (JSON)');
            $table->json('recommendations')->nullable()->comment('建議事項 (JSON)');

            // 學習數據
            $table->integer('study_hours')->nullable()->comment('學習時數');
            $table->integer('tasks_completed')->nullable()->comment('完成任務數');
            $table->integer('courses_attended')->nullable()->comment('參加課程數');

            // 狀態
            $table->enum('status', ['draft', 'in_review', 'completed', 'cancelled'])->default('draft')->comment('分析狀態');
            $table->timestamp('submitted_at')->nullable()->comment('提交時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');

            $table->timestamps();

            $table->index(['prescription_id', 'status']);
        });

        // ==========================================
        // 5. 任務管理層 (Task Management Layer)
        // ==========================================

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flip_course_case_id')->constrained('flip_course_cases')->onDelete('cascade')->comment('所屬案例');
            $table->foreignId('assignee_id')->constrained('members')->onDelete('cascade')->comment('負責人');
            $table->morphs('taskable'); // 關聯的業務實體 (prescription/assessment/case)

            // 任務類型
            $table->enum('type', [
                // 規劃師任務
                'create_line_group',        // 建立 Line 群組
                'confirm_payment',          // 確認金流
                'assign_counselor',         // 指派諮商師
                'assign_analyst',           // 指派分析師

                // 諮商師任務
                'create_strategy',          // 建立學習策略
                'conduct_counseling',       // 進行諮商
                'issue_prescription',       // 開立處方簽
                'review_analysis',          // 審查分析報告
                'adjust_strategy',          // 調整學習策略

                // 分析師任務
                'create_assessment',        // 建立測驗
                'analyze_results',          // 分析學習成果
                'submit_analysis',          // 提交分析報告

                // 學生任務
                'complete_learning_task',   // 完成學習任務
                'attend_course',            // 參加課程
                'take_assessment'           // 參加測驗
            ])->comment('任務類型');

            // 任務狀態
            $table->enum('status', [
                'pending',      // 待處理
                'in_progress',  // 進行中
                'completed',    // 已完成
                'blocked',      // 被阻擋（等待其他任務）
                'cancelled'     // 已取消
            ])->default('pending')->comment('任務狀態');

            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->comment('優先級');

            // 任務內容
            $table->string('title')->comment('任務標題');
            $table->text('description')->nullable()->comment('任務描述');
            $table->json('metadata')->nullable()->comment('額外資訊 (JSON)');

            // 時間管理
            $table->timestamp('due_date')->nullable()->comment('截止日期');
            $table->timestamp('started_at')->nullable()->comment('開始時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');

            $table->timestamps();

            // 索引優化
            $table->index(['assignee_id', 'status']);
            $table->index(['flip_course_case_id', 'status']);
            $table->index(['type', 'status']);
        });

        // 任務依賴關係 (可選 - 用於複雜的任務流程)
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade')->comment('任務');
            $table->foreignId('depends_on_task_id')->constrained('tasks')->onDelete('cascade')->comment('依賴的任務');
            $table->timestamps();

            $table->unique(['task_id', 'depends_on_task_id'], 'unique_task_dependency');
        });

        // ==========================================
        // 6. 案例備註/日誌 (Case Notes/Logs)
        // ==========================================

        Schema::create('flip_course_case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flip_course_case_id')->constrained('flip_course_cases')->onDelete('cascade')->comment('案例');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('記錄者');
            $table->enum('note_type', ['general', 'counseling', 'analysis', 'planning', 'issue'])->default('general')->comment('備註類型');
            $table->text('content')->comment('備註內容');
            $table->json('attachments')->nullable()->comment('附件 (JSON)');
            $table->timestamps();

            $table->index(['flip_course_case_id', 'note_type']);
        });

        // ==========================================
        // 7. 修改現有的 counseling_appointments 表以支援翻轉課程
        // ==========================================

        Schema::table('counseling_appointments', function (Blueprint $table) {
            // 將 order_item_id 改為可選，因為翻轉課程不是透過一般訂單購買
            $table->foreignId('order_item_id')
                  ->nullable()
                  ->change();

            // 新增關聯翻轉課程案例
            $table->foreignId('flip_course_case_id')
                  ->nullable()
                  ->after('order_item_id')
                  ->constrained('flip_course_cases')
                  ->onDelete('cascade')
                  ->comment('關聯的翻轉課程案例（如果是翻轉課程諮商）');

            // 新增索引
            $table->index('flip_course_case_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 先還原 counseling_appointments 的修改
        Schema::table('counseling_appointments', function (Blueprint $table) {
            $table->dropForeign(['flip_course_case_id']);
            $table->dropIndex(['flip_course_case_id']);
            $table->dropColumn('flip_course_case_id');
        });

        // 再刪除翻轉課程相關的表
        Schema::dropIfExists('flip_course_case_notes');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('learning_tasks');
        Schema::dropIfExists('prescription_club_courses');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('flip_course_cases');
        Schema::dropIfExists('lang_type_flip_course_info');
        Schema::dropIfExists('flip_course_infos');
    }
};
