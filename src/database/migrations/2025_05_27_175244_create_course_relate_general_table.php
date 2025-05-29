<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// TODO: Add flip course
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_info_reviews', function (Blueprint $table) {
            $table->id();
            $table->float('average_rating')->default(0)->comment('平均評價分數');
            $table->integer('total_reviews')->default(0)->comment('評價總數');
            $table->float('average_attendance_record')->nullable()->comment('平均出席紀錄');
            $table->timestamps();
        });

        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->float('rating')->default(0)->comment('評價分數');
            $table->text('comment')->nullable()->comment('留言');
            $table->timestamps();
        });

        Schema::create('course_resources', function (Blueprint $table) {
            $table->id();
            $table->string('material_link')->nullable()->comment('今日教材連結');
            $table->string('replay_link')->nullable()->comment('回放影片連結');
            $table->string('replay_title')->nullable()->comment('回放影片標題');
            $table->unsignedInteger('replay_duration')->nullable()->comment('回放影片時長（以秒為單位）');
            $table->timestamps();
        });

        // Relation
        Schema::create('club_course_course_info_review', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade')->comment('課程');
            $table->foreignId('course_info_review_id')->constrained('course_info_reviews')->onDelete('cascade')->comment('課程資訊');
            $table->timestamps();
        });

        Schema::create('member_course_review', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('學生');
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade')->comment('課程');
            $table->timestamps();
        });

        Schema::create('course_resource_club_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_resource_id')->constrained('course_resources')->onDelete('cascade')->comment('課程教材');
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade')->comment('課程');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 先刪除依賴 club_courses 的外鍵約束
        Schema::table('course_status_type_club_course', function (Blueprint $table) {
            $table->dropForeign(['club_course_id']);
        });

        // 再刪除其他表
        Schema::dropIfExists('club_course_course_info_review');
        Schema::dropIfExists('course_resource_club_course');
        Schema::dropIfExists('member_course_review');
        Schema::dropIfExists('course_resources');
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('course_info_reviews');
        Schema::dropIfExists('course_status_type_club_course');
        Schema::dropIfExists('club_courses');
    }
};
