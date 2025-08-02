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
        Schema::create('zoom_meet_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_course_id')->constrained('club_courses')->onDelete('cascade')->comment('課程ID');
            $table->foreignId('zoom_credential_id')->constrained('zoom_credentials')->onDelete('cascade')->comment('使用的 Zoom 憑證');
            $table->string('zoom_meeting_id')->unique()->comment('Zoom 會議 ID');
            $table->string('zoom_meeting_uuid')->nullable()->comment('Zoom 會議 UUID');
            $table->string('host_id')->nullable()->comment('主持人 ID');
            $table->string('topic')->comment('會議主題');
            $table->integer('type')->default(2)->comment('會議類型 (1:即時, 2:預定, 3:定期無固定時間, 8:定期固定時間)');
            $table->datetime('start_time')->comment('會議開始時間');
            $table->integer('duration')->comment('會議持續時間（分鐘）');
            $table->string('timezone')->default('Asia/Taipei')->comment('時區');
            $table->text('agenda')->nullable()->comment('會議議程');
            $table->string('password')->nullable()->comment('會議密碼');
            $table->string('h323_password')->nullable()->comment('H323/SIP 密碼');
            $table->string('pstn_password')->nullable()->comment('電話撥入密碼');
            $table->string('encrypted_password')->nullable()->comment('加密密碼');
            $table->text('join_url')->comment('參與者加入連結');
            $table->text('start_url')->comment('主持人開始連結');
            $table->string('link')->nullable()->comment('會議連結（暫時保留）');
            $table->json('settings')->nullable()->comment('會議設定');
            $table->enum('status', ['active', 'ended', 'cancelled'])->default('active')->comment('會議狀態');
            $table->datetime('zoom_created_at')->nullable()->comment('Zoom 會議創建時間');
            $table->timestamps();
            
            // 索引
            $table->index('club_course_id');
            $table->index('zoom_credential_id');
            $table->index('zoom_meeting_id');
            $table->index('start_time');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_meet_detail');
    }
};