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
        Schema::create('zoom_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('帳號名稱/描述');
            $table->string('account_id')->comment('Zoom Account ID');
            $table->string('client_id')->comment('Zoom Client ID');
            $table->text('client_secret')->comment('Zoom Client Secret (加密儲存)');
            $table->string('email')->nullable()->comment('Zoom 帳號 Email');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->integer('max_concurrent_meetings')->default(2)->comment('最大併發會議數');
            $table->integer('current_meetings')->default(0)->comment('目前進行中的會議數');
            $table->timestamp('last_used_at')->nullable()->comment('最後使用時間');
            $table->json('settings')->nullable()->comment('額外設定');
            $table->timestamps();
            
            $table->unique('account_id');
            $table->index(['is_active', 'current_meetings']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_credentials');
    }
};
