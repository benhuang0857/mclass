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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('接收通知的用戶');
            $table->enum('type', [
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
                'course_follower',
                'counselor_new_service',
                'counselor_specific',
                'flip_case_assigned',
                'flip_task_assigned',
                'flip_prescription_issued',
                'flip_analysis_completed',
                'flip_cycle_started',
                'flip_case_completed'
            ])->comment('通知類型');
            $table->enum('related_type', [
                'course',
                'counseling',
                'counselor',
                'product',
                'flip_course_case',
                'prescription',
                'assessment',
                'task'
            ])->comment('關聯對象類型');
            $table->unsignedBigInteger('related_id')->comment('關聯對象ID');
            $table->string('title')->comment('通知標題');
            $table->text('content')->comment('通知內容');
            $table->json('data')->nullable()->comment('額外數據');
            $table->boolean('is_read')->default(false)->comment('是否已讀');
            $table->timestamp('scheduled_at')->nullable()->comment('預定發送時間');
            $table->timestamp('sent_at')->nullable()->comment('實際發送時間');
            $table->timestamps();

            // 索引優化
            $table->index(['member_id', 'is_read']);
            $table->index(['type', 'scheduled_at']);
            $table->index(['related_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};