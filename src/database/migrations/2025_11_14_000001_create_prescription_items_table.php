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
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')
                  ->constrained('prescriptions')
                  ->onDelete('cascade')
                  ->comment('所屬處方簽');

            // 項目類型
            $table->enum('item_type', [
                'task',         // 學習任務
                'course',       // 課程建議
                'resource',     // 學習資源
                'assessment',   // 測驗/評量
                'note',         // 備註說明
                'goal',         // 學習目標
                'other'         // 其他
            ])->comment('處方項目類型');

            // 基本資訊
            $table->string('title')->comment('項目標題');
            $table->text('description')->nullable()->comment('項目描述');

            // 彈性資料存放 (依據不同 item_type 可能有不同結構)
            // 例如：
            // - task: {due_date, estimated_hours, priority}
            // - course: {course_id, recommended_sessions}
            // - resource: {url, file_path, resource_type}
            // - assessment: {test_type, passing_score}
            $table->json('metadata')->nullable()->comment('項目元資料 (JSON)');

            // 排序與狀態
            $table->integer('sort_order')->default(0)->comment('排序順序');
            $table->enum('status', [
                'pending',      // 待處理
                'active',       // 進行中
                'completed',    // 已完成
                'cancelled'     // 已取消
            ])->default('pending')->comment('項目狀態');

            // 完成追蹤
            $table->text('completion_notes')->nullable()->comment('完成備註');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');

            $table->timestamps();

            // 索引
            $table->index(['prescription_id', 'item_type']);
            $table->index(['prescription_id', 'status']);
            $table->index(['prescription_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
