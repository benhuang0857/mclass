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
        // 添加 title 字段到 comments 表
        Schema::table('comments', function (Blueprint $table) {
            $table->string('title', 255)->nullable()->after('parent_id')->comment('評論標題');
        });

        // 創建 comment_lang_type 中間表（多對多關聯）
        Schema::create('comment_lang_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->onDelete('cascade');
            $table->foreignId('lang_type_id')->constrained('lang_types')->onDelete('cascade');
            $table->timestamps();

            // 確保同一評論和語言類型組合唯一
            $table->unique(['comment_id', 'lang_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_lang_type');

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
