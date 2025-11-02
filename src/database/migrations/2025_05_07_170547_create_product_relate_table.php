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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('商品名稱');
            $table->string('code')->unique()->comment('商品代碼');
            $table->string('feature_img')->comment('商品圖片');
            $table->float('regular_price')->default(0)->comment('費用');
            $table->float('discount_price')->default(0)->comment('折價後費用');
            $table->boolean('limit_enrollment')->default(false)->comment('是否限制報名上限人數');
            $table->integer('max_enrollment')->default(1)->comment('報名上限人數');
            $table->integer('stock')->default(0)->comment('庫存');
            $table->boolean('is_series')->default(false)->comment('是否為系列課程的一部分');
            $table->boolean('elective')->default(false)->comment('是否開放選修');
            $table->boolean('is_visible_to_specific_students')->default(false)->comment('僅指定學員可見');
            $table->enum('status', ['published', 'unpublished', 'sold-out'])->default('unpublished')->comment('商品狀態');
            $table->timestamps();
        });

        Schema::create('follower_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('追蹤者');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('visibler_club_course_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->comment('可看到的學生');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visibler_club_course_info');
        Schema::dropIfExists('follower_club_course_info');
        Schema::dropIfExists('products');
    }
};
