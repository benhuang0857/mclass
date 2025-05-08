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
         /**
         * club_course_infos relates
         */
        Schema::create('club_course_infos', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('課程名稱');
            $table->string('code')->unique()->comment('課程代碼');
            $table->text('description')->comment('課程簡介／目標');
            $table->text('details')->comment('課程介紹與規劃');
            $table->string('feature_img')->comment('主視覺圖片 (16:9 比例)');
            $table->json('teaching_modes')->comment('上課方式');
            $table->string('schedule_display')->comment('上課時間（文字顯示）');
            $table->boolean('is_periodic')->default(false)->comment('是否為週期性課程');
            $table->boolean('elective')->default(false)->comment('是否開放選修');
            $table->integer('max_enrollment')->default(1)->comment('報名上限人數');
            $table->integer('total_sessions')->default(1)->comment('總開課堂數');
            $table->boolean('allow_replay')->default(true)->comment('是否允許回放');
            $table->boolean('is_series')->default(false)->comment('是否為系列課程的一部分');
            $table->enum('status', ['Published', 'Unpublished', 'Completed', 'Pending'])->default('Unpublished')->comment('課程狀態');
            $table->boolean('is_visible_to_specific_students')->default(false)->comment('僅指定學員可見');
            $table->timestamps();
        });
    
        Schema::create('club_course_info_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('club_course_infos')->onDelete('cascade')->comment('對應的課程資訊 ID');
            $table->date('start_date')->comment('週期開始日期');
            $table->date('end_date')->comment('週期結束日期');
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->comment('星期幾');
            $table->time('start_time')->comment('開始時間');
            $table->time('end_time')->comment('結束時間');
            $table->timestamps();
            $table->unique(['course_id', 'day_of_week', 'start_time', 'end_time'], 'unique_schedule');
        });
    
        Schema::create('club_course_info_review', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('club_course_infos')->onDelete('cascade')->comment('對應的課程資訊 ID');
            $table->float('average_rating')->default(0)->comment('平均評價分數');
            $table->integer('total_reviews')->default(0)->comment('評價總數');
            $table->float('average_attendance_record')->nullable()->comment('平均出席紀錄');
            $table->timestamps();
        });

        /** Relation */
        Schema::create('lang_type_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lang_type_id')->constrained('lang_types')->onDelete('cascade')->comment('語言');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('level_type_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_type_id')->constrained('level_types')->onDelete('cascade')->comment('等級');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('course_info_type_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_info_type_id')->constrained('course_info_types')->onDelete('cascade')->comment('課堂種類');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('teach_method_type_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teach_method_type_id')->constrained('teach_method_types')->onDelete('cascade')->comment('授課形式');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('sysman_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('操作人員');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('teacher_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('教師');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('assistant_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('教師');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('student_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('學生');
            $table->foreignId('club_course_info_id')->constrained('club_course_infos')->onDelete('cascade');
            $table->timestamps();
        });
    
        ####################################################################################################

        /**
         * club_courses relates
         */
        Schema::create('club_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('club_course_infos')->onDelete('cascade')->comment('對應的課程資訊 ID');
            $table->datetime('start_time')->comment('課程開始時間');
            $table->datetime('end_time')->comment('課程結束時間');
            $table->string('link')->nullable()->comment('今日課程連結');
            $table->string('location')->nullable()->comment('今日課程位置');
            $table->boolean('trial')->default(true)->comment('是否為試聽課程');
            $table->unsignedInteger('sort')->default(0);
            $table->enum('status', ['Active', 'Cancelled', 'Completed'])->default('Active')->comment('課程狀態');
            $table->timestamps();
        });
    
        Schema::create('club_course_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('club_courses')->onDelete('cascade')->comment('對應的課程 ID');
            $table->string('material_link')->nullable()->comment('今日教材連結');
            $table->string('replay_link')->nullable()->comment('回放影片連結');
            $table->string('replay_title')->nullable()->comment('回放影片標題');
            $table->unsignedInteger('replay_duration')->nullable()->comment('回放影片時長（以秒為單位）');
            $table->timestamps();
        });

        Schema::create('sysman_club_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('操作人員');
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('teacher_club_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('教師');
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('student_club_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('學生');
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_club_course');
        Schema::dropIfExists('teacher_club_course');
        Schema::dropIfExists('sysman_club_course');
        Schema::dropIfExists('club_course_resources');
        Schema::dropIfExists('club_courses');
        Schema::dropIfExists('student_club_course_info');
        Schema::dropIfExists('assistant_club_course_info');
        Schema::dropIfExists('teacher_club_course_info');
        Schema::dropIfExists('sysman_club_course_info');
        Schema::dropIfExists('teach_method_type_club_course_info');
        Schema::dropIfExists('course_info_type_club_course_info');
        Schema::dropIfExists('level_type_club_course_info');
        Schema::dropIfExists('lang_type_club_course_info');
        Schema::dropIfExists('club_course_info_review');
        Schema::dropIfExists('club_course_info_schedule');
        Schema::dropIfExists('club_course_infos');
    }
};
