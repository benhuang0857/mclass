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
        Schema::create('flip_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('對應的商品 ID');
            $table->string('name')->comment('課程名稱');
            $table->string('code')->unique()->comment('課程代碼');
            $table->text('description')->comment('課程簡介/目標');
            $table->text('details')->comment('課程介紹與規劃');
            $table->string('feature_img')->comment('主視覺圖片 (16:9 比例)');
            $table->enum('teaching_mode', ['online', 'offline', 'hybrid'])->comment('上課方式: 線上/實體/混合');
            $table->timestamps();
        });

        /** Relation */
        Schema::create('lang_type_flip_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lang_type_id')->constrained('lang_types')->onDelete('cascade')->comment('語言');
            $table->foreignId('flip_course_info_id')->constrained('flip_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('sysman_flip_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('操作人員');
            $table->foreignId('flip_course_info_id')->constrained('flip_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('counselor_flip_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('諮商師');
            $table->foreignId('flip_course_info_id')->constrained('flip_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('analyst_flip_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('教師');
            $table->foreignId('flip_course_info_id')->constrained('flip_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('cycle_flip_course_info', function (Blueprint $table) {
            $table->id();
            $table->integer('round')->comment('第幾次');
            $table->foreignId('flip_course_info_id')->constrained('flip_course_infos')->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flip_course_relate');
    }
};
