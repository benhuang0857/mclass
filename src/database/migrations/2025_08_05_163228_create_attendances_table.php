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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade')->comment('課程ID');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('學生ID');
            $table->enum('status', ['present', 'absent', 'late', 'early_leave', 'excused'])->default('absent')->comment('出席狀態');
            $table->datetime('check_in_time')->nullable()->comment('實際簽到時間');
            $table->datetime('check_out_time')->nullable()->comment('實際簽退時間');
            $table->text('note')->nullable()->comment('備註 (如請假原因)');
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null')->comment('點名者 (教師/助教)');
            $table->datetime('marked_at')->comment('點名時間');
            $table->timestamps();
            
            // 確保每個課程的每個學生只有一筆出席記錄
            $table->unique(['club_course_id', 'member_id'], 'unique_course_member_attendance');
            
            // 索引優化
            $table->index(['club_course_id', 'status']);
            $table->index(['member_id', 'status']);
            $table->index('marked_at');
        });

        // 建立出席統計表
        Schema::create('attendance_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('學生ID');
            $table->foreignId('club_course_info_id')->nullable()->constrained('club_course_infos')->onDelete('cascade')->comment('課程資訊ID (可選)');
            $table->integer('total_sessions')->default(0)->comment('總課程數');
            $table->integer('present_count')->default(0)->comment('出席次數');
            $table->integer('absent_count')->default(0)->comment('缺席次數');
            $table->integer('late_count')->default(0)->comment('遲到次數');
            $table->integer('early_leave_count')->default(0)->comment('早退次數');
            $table->integer('excused_count')->default(0)->comment('請假次數');
            $table->decimal('attendance_rate', 5, 2)->default(0)->comment('出席率 (百分比)');
            $table->date('period_start')->nullable()->comment('統計期間開始');
            $table->date('period_end')->nullable()->comment('統計期間結束');
            $table->timestamps();
            
            // 索引
            $table->index(['member_id', 'club_course_info_id']);
            $table->index('attendance_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_statistics');
        Schema::dropIfExists('attendances');
    }
};
