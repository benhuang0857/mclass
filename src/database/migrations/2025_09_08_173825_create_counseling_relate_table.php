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
        // 諮商資訊主表 (類似 club_course_infos)
        Schema::create('counseling_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('產品ID');
            $table->string('name')->comment('諮商服務名稱');
            $table->string('code')->unique()->comment('諮商服務代碼');
            $table->text('description')->nullable()->comment('服務描述');
            $table->text('details')->nullable()->comment('詳細內容');
            $table->string('feature_img')->nullable()->comment('特色圖片');
            $table->enum('counseling_mode', ['online', 'offline', 'both'])->default('online')->comment('諮商模式');
            $table->integer('session_duration')->default(60)->comment('單次諮商時長(分鐘)');
            $table->integer('total_sessions')->default(1)->comment('總諮商次數');
            $table->boolean('allow_reschedule')->default(true)->comment('允許重新預約');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->comment('狀態');
            $table->timestamps();
        });

        // 諮商師關聯表
        Schema::create('counseling_info_counselors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counseling_info_id')->constrained('counseling_infos')->onDelete('cascade')->comment('諮商服務ID');
            $table->foreignId('counselor_id')->constrained('members')->onDelete('cascade')->comment('諮商師ID');
            $table->boolean('is_primary')->default(false)->comment('是否為主要諮商師');
            $table->timestamps();
        });

        // 諮商預約表
        Schema::create('counseling_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade')->comment('訂單項目ID');
            $table->foreignId('counseling_info_id')->constrained('counseling_infos')->onDelete('cascade')->comment('諮商服務ID');
            $table->foreignId('student_id')->constrained('members')->onDelete('cascade')->comment('學員ID');
            $table->foreignId('counselor_id')->constrained('members')->onDelete('cascade')->comment('諮商師ID');
            $table->string('title')->comment('預約主題');
            $table->text('description')->nullable()->comment('問題描述');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending')->comment('狀態');
            $table->enum('type', ['academic', 'career', 'personal', 'other'])->default('academic')->comment('諮商類型');
            $table->datetime('preferred_datetime')->comment('希望時間');
            $table->datetime('confirmed_datetime')->nullable()->comment('確認時間');
            $table->integer('duration')->default(60)->comment('時長(分鐘)');
            $table->enum('method', ['online', 'offline'])->default('online')->comment('諮商方式');
            $table->string('location')->nullable()->comment('地點');
            $table->string('meeting_url')->nullable()->comment('線上會議連結');
            $table->text('counselor_notes')->nullable()->comment('諮商師備註');
            $table->text('student_feedback')->nullable()->comment('學員回饋');
            $table->integer('rating')->nullable()->comment('評分 1-5');
            $table->boolean('is_urgent')->default(false)->comment('是否緊急');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_appointments');
        Schema::dropIfExists('counseling_info_counselors');
        Schema::dropIfExists('counseling_infos');
    }
};
