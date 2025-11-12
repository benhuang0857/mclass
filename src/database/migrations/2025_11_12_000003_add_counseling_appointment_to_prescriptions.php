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
        Schema::table('prescriptions', function (Blueprint $table) {
            // 關聯諮商會議（可選，因為處方簽可能基於多次諮商）
            $table->foreignId('counseling_appointment_id')
                  ->nullable()
                  ->after('counselor_id')
                  ->constrained('counseling_appointments')
                  ->onDelete('set null')
                  ->comment('關聯的諮商會議');
        });

        // 修改 counseling_appointments 表，支援翻轉課程
        Schema::table('counseling_appointments', function (Blueprint $table) {
            // 將 order_item_id 改為可選，因為翻轉課程不是透過一般訂單購買
            $table->foreignId('order_item_id')
                  ->nullable()
                  ->change();

            // 新增關聯翻轉課程案例（多態關聯的另一種方式）
            $table->foreignId('flip_course_case_id')
                  ->nullable()
                  ->after('order_item_id')
                  ->constrained('flip_course_cases')
                  ->onDelete('cascade')
                  ->comment('關聯的翻轉課程案例（如果是翻轉課程諮商）');

            // 新增索引
            $table->index('flip_course_case_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('counseling_appointments', function (Blueprint $table) {
            $table->dropForeign(['flip_course_case_id']);
            $table->dropColumn('flip_course_case_id');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['counseling_appointment_id']);
            $table->dropColumn('counseling_appointment_id');
        });
    }
};
