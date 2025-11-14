<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\FlipCourseInfo;
use App\Models\LangType;
use App\Models\Member;
use App\Models\Role;
use App\Models\Order;
use App\Models\FlipCourseCase;

class FlipCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('開始建立翻轉課程測試資料...');

        // 確認語言類型存在
        $langTypes = LangType::all();
        if ($langTypes->isEmpty()) {
            $this->command->warn('沒有找到語言類型資料，請先執行 DatabaseSeeder');
            return;
        }

        // 1. 建立翻轉課程商品與模板
        $flipCourses = [
            [
                'product' => [
                    'name' => '翻轉英文課程 - 初階',
                    'code' => 'FLIP-EN-BEGINNER',
                    'feature_img' => 'https://via.placeholder.com/1600x900/4CAF50/FFFFFF?text=Flip+English+Beginner',
                    'regular_price' => 18000.00,
                    'discount_price' => 15000.00,
                    'limit_enrollment' => false,
                    'max_enrollment' => 0,
                    'stock' => 999,
                    'is_series' => false,
                    'elective' => false,
                    'is_visible_to_specific_students' => false,
                    'status' => 'published',
                ],
                'info' => [
                    'name' => '翻轉英文課程 - 初階',
                    'code' => 'FLIP-EN-001',
                    'description' => '專為英文初學者設計的翻轉課程，透過諮商師、分析師的協作，為您打造個人化學習計畫',
                    'details' => '本課程採用翻轉教室模式，由專業團隊協助您：\n1. 諮商師評估您的學習需求並制定策略\n2. 根據您的進度推薦適合的俱樂部課程\n3. 分析師定期評估學習成果\n4. 循環優化學習計畫',
                    'feature_img' => 'https://via.placeholder.com/1600x900/4CAF50/FFFFFF?text=Flip+English+Beginner',
                    'teaching_mode' => 'hybrid',
                    'status' => 'published',
                ],
                'lang_types' => ['english'],
            ],
            [
                'product' => [
                    'name' => '翻轉英文課程 - 中階',
                    'code' => 'FLIP-EN-INTERMEDIATE',
                    'feature_img' => 'https://via.placeholder.com/1600x900/2196F3/FFFFFF?text=Flip+English+Intermediate',
                    'regular_price' => 22000.00,
                    'discount_price' => 18000.00,
                    'limit_enrollment' => false,
                    'max_enrollment' => 0,
                    'stock' => 999,
                    'is_series' => false,
                    'elective' => false,
                    'is_visible_to_specific_students' => false,
                    'status' => 'published',
                ],
                'info' => [
                    'name' => '翻轉英文課程 - 中階',
                    'code' => 'FLIP-EN-002',
                    'description' => '適合有英文基礎的學員，透過系統化的循環學習，突破學習瓶頸',
                    'details' => '本課程提供：\n1. 專業諮商師的學習策略指導\n2. 客製化的課程與任務安排\n3. 定期的學習成果分析\n4. 持續優化的學習循環',
                    'feature_img' => 'https://via.placeholder.com/1600x900/2196F3/FFFFFF?text=Flip+English+Intermediate',
                    'teaching_mode' => 'online',
                    'status' => 'published',
                ],
                'lang_types' => ['english'],
            ],
            [
                'product' => [
                    'name' => '翻轉普通話課程 - 初階',
                    'code' => 'FLIP-CH-BEGINNER',
                    'feature_img' => 'https://via.placeholder.com/1600x900/FF9800/FFFFFF?text=Flip+Mandarin+Beginner',
                    'regular_price' => 16000.00,
                    'discount_price' => 13000.00,
                    'limit_enrollment' => false,
                    'max_enrollment' => 0,
                    'stock' => 999,
                    'is_series' => false,
                    'elective' => false,
                    'is_visible_to_specific_students' => false,
                    'status' => 'published',
                ],
                'info' => [
                    'name' => '翻轉普通話課程 - 初階',
                    'code' => 'FLIP-CH-001',
                    'description' => '零基礎也能輕鬆學習普通話，專業團隊全程陪伴',
                    'details' => '翻轉式學習特色：\n1. 一對一諮商評估\n2. 量身打造學習計畫\n3. 定期追蹤與調整\n4. 專業分析師評估成效',
                    'feature_img' => 'https://via.placeholder.com/1600x900/FF9800/FFFFFF?text=Flip+Mandarin+Beginner',
                    'teaching_mode' => 'hybrid',
                    'status' => 'published',
                ],
                'lang_types' => ['mandarin'],
            ],
        ];

        foreach ($flipCourses as $courseData) {
            // 建立商品
            $product = Product::create($courseData['product']);
            $this->command->info("✓ 已建立商品: {$product->name}");

            // 建立翻轉課程資訊
            $flipCourseInfo = FlipCourseInfo::create(array_merge(
                $courseData['info'],
                ['product_id' => $product->id]
            ));

            // 關聯語言類型
            $langTypeIds = LangType::whereIn('slug', $courseData['lang_types'])->pluck('id');
            $flipCourseInfo->langTypes()->attach($langTypeIds);

            $this->command->info("✓ 已建立翻轉課程模板: {$flipCourseInfo->name}");
        }

        // 2. 建立範例案例（可選）
        $this->createSampleCase();

        $this->command->info('✅ 翻轉課程測試資料建立完成！');
    }

    /**
     * 建立一個範例案例用於測試工作流
     */
    private function createSampleCase(): void
    {
        // 取得第一個翻轉課程
        $flipCourseInfo = FlipCourseInfo::first();
        if (!$flipCourseInfo) {
            $this->command->warn('⚠ 沒有翻轉課程模板，跳過建立範例案例');
            return;
        }

        // 取得角色
        $studentRole = Role::where('slug', 'student')->first();
        $plannerRole = Role::where('slug', 'planner')->first();
        $counselorRole = Role::where('slug', 'counselor')->first();
        $analystRole = Role::where('slug', 'analyst')->first();

        // 根據角色選擇會員
        $student = $studentRole ? Member::whereHas('roles', function($q) use ($studentRole) {
            $q->where('roles.id', $studentRole->id);
        })->first() : null;

        $planner = $plannerRole ? Member::whereHas('roles', function($q) use ($plannerRole) {
            $q->where('roles.id', $plannerRole->id);
        })->first() : null;

        $counselor = $counselorRole ? Member::whereHas('roles', function($q) use ($counselorRole) {
            $q->where('roles.id', $counselorRole->id);
        })->first() : null;

        $analyst = $analystRole ? Member::whereHas('roles', function($q) use ($analystRole) {
            $q->where('roles.id', $analystRole->id);
        })->first() : null;

        // 檢查是否所有角色都有對應的會員
        if (!$student || !$planner || !$counselor || !$analyst) {
            $this->command->warn('⚠ 缺少必要角色的會員，跳過建立範例案例');
            $this->command->warn('  需要: 學生、規劃師、諮商師、分析師');
            return;
        }

        // 建立訂單
        $order = Order::create([
            'member_id' => $student->id,
            'code' => 'ORD-FLIP-' . date('Ymd') . '-001',
            'total' => $flipCourseInfo->product->discount_price ?? $flipCourseInfo->product->regular_price,
            'currency' => 'TWD',
            'status' => 'pending',
            'note' => '測試用翻轉課程訂單',
        ]);

        // 建立訂單項目
        $order->orderItems()->create([
            'product_id' => $flipCourseInfo->product_id,
            'product_name' => $flipCourseInfo->name,
            'quantity' => 1,
            'price' => $flipCourseInfo->product->discount_price ?? $flipCourseInfo->product->regular_price,
            'options' => json_encode([
                'flip_course_info_id' => $flipCourseInfo->id,
                'planner_id' => $planner->id,
            ]),
        ]);

        // 建立案例
        $case = FlipCourseCase::create([
            'flip_course_info_id' => $flipCourseInfo->id,
            'student_id' => $student->id,
            'order_id' => $order->id,
            'planner_id' => $planner->id,
            'counselor_id' => null, // 待規劃師指派
            'analyst_id' => null,   // 待規劃師指派
            'workflow_stage' => 'created',
            'payment_status' => 'pending',
            'cycle_count' => 0,
        ]);

        $this->command->info("✓ 已建立範例案例 #{$case->id}");
        $this->command->info("  - 學生: Member #{$student->id}");
        $this->command->info("  - 規劃師: Member #{$planner->id}");
        $this->command->info("  - 訂單: {$order->code}");
    }
}
