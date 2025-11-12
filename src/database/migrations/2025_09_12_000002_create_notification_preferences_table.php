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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('用戶ID');
            $table->enum('notification_type', [
                'course_reminder',
                'course_change',
                'course_new_class',
                'course_price_change',
                'course_status_change',
                'course_registration_deadline',
                'counseling_reminder',
                'counseling_confirmed',
                'counseling_status_change',
                'counseling_time_change',
                'counselor_new_service',
                'flip_case_assigned',           // 翻轉課程案例指派
                'flip_task_assigned',           // 翻轉課程任務指派
                'flip_prescription_issued',     // 處方簽開立
                'flip_analysis_completed',      // 分析報告完成
                'flip_cycle_started',           // 新循環開始
                'flip_case_completed',          // 案例完成
                'all' // 全部類型的總開關
            ])->comment('通知類型');
            $table->boolean('enabled')->default(true)->comment('是否啟用');
            $table->json('delivery_methods')->comment('推送方式 [database, email, push, sms]');
            $table->integer('advance_minutes')->nullable()->comment('提前多久提醒（分鐘）');
            $table->json('schedule_settings')->nullable()->comment('排程設定（如靜音時間等）');
            $table->timestamps();

            // 確保每個用戶每個通知類型只有一個設定
            $table->unique(['member_id', 'notification_type']);
            
            // 索引優化
            $table->index(['member_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};