<?php

namespace Database\Seeders;

use App\Models\CounselingAppointment;
use App\Models\CounselingInfo;
use App\Models\Member;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CounselingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 首先建立必要的基礎資料
        $this->createCounselingProducts();

        // 建立諮商資訊
        $counselingInfos = $this->createCounselingInfos();

        // 建立諮商師關聯
        $this->createCounselorRelations($counselingInfos);

        // 建立預約範例
        $this->createSampleAppointments($counselingInfos);
    }

    /**
     * 建立諮商產品資料
     */
    private function createCounselingProducts(): void
    {
        $products = [
            [
                'name' => '學業諮商服務',
                'code' => 'COUNSELING-ACADEMIC-001',
                'feature_img' => 'products/academic-counseling.jpg',
                'regular_price' => 1500.00,
                'discount_price' => 1350.00,
                'limit_enrollment' => true,
                'max_enrollment' => 50,
                'stock' => 50,
                'is_series' => false,
                'elective' => true,
                'is_visible_to_specific_students' => false,
                'status' => 'published',
            ],
            [
                'name' => '職涯規劃諮商',
                'code' => 'COUNSELING-CAREER-002',
                'feature_img' => 'products/career-counseling.jpg',
                'regular_price' => 1800.00,
                'discount_price' => 1620.00,
                'limit_enrollment' => true,
                'max_enrollment' => 30,
                'stock' => 30,
                'is_series' => false,
                'elective' => true,
                'is_visible_to_specific_students' => false,
                'status' => 'published',
            ],
            [
                'name' => '心理健康諮商',
                'code' => 'COUNSELING-PERSONAL-003',
                'feature_img' => 'products/personal-counseling.jpg',
                'regular_price' => 2000.00,
                'discount_price' => 1800.00,
                'limit_enrollment' => true,
                'max_enrollment' => 20,
                'stock' => 20,
                'is_series' => false,
                'elective' => true,
                'is_visible_to_specific_students' => false,
                'status' => 'published',
            ],
            [
                'name' => '綜合諮商套餐',
                'code' => 'COUNSELING-COMBO-004',
                'feature_img' => 'products/combo-counseling.jpg',
                'regular_price' => 4500.00,
                'discount_price' => 3900.00,
                'limit_enrollment' => true,
                'max_enrollment' => 15,
                'stock' => 15,
                'is_series' => true,
                'elective' => true,
                'is_visible_to_specific_students' => false,
                'status' => 'published',
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['code' => $productData['code']],
                $productData
            );
        }
    }

    /**
     * 建立諮商資訊
     */
    private function createCounselingInfos(): array
    {
        $products = Product::whereIn('code', [
            'COUNSELING-ACADEMIC-001',
            'COUNSELING-CAREER-002', 
            'COUNSELING-PERSONAL-003',
            'COUNSELING-COMBO-004'
        ])->get();

        $counselingInfosData = [
            [
                'name' => '學業規劃與學習策略諮商',
                'code' => 'COUNSELING-INFO-ACADEMIC-001',
                'description' => '協助學生解決學習困難，規劃學業發展方向',
                'details' => '提供個人化的學業指導，包含選課建議、學習方法改善、時間管理、考試策略等。適合面臨學業困擾或需要學習指導的學生。',
                'feature_img' => 'counseling/academic-counseling.jpg',
                'counseling_mode' => 'both',
                'session_duration' => 60,
                'total_sessions' => 1,
                'allow_reschedule' => true,
                'status' => 'active',
            ],
            [
                'name' => '職涯探索與規劃諮商',
                'code' => 'COUNSELING-INFO-CAREER-002',
                'description' => '協助學生探索職業興趣，規劃未來職涯發展',
                'details' => '透過職業興趣測驗、能力分析、市場趨勢討論等方式，協助學生找到適合的職業方向，並制定實際的職涯規劃。',
                'feature_img' => 'counseling/career-counseling.jpg',
                'counseling_mode' => 'both',
                'session_duration' => 90,
                'total_sessions' => 1,
                'allow_reschedule' => true,
                'status' => 'active',
            ],
            [
                'name' => '心理健康與壓力管理諮商',
                'code' => 'COUNSELING-INFO-PERSONAL-003',
                'description' => '提供心理支持，協助處理情緒困擾和壓力問題',
                'details' => '針對學生常見的壓力、焦慮、人際關係等問題提供專業諮商服務。採用認知行為療法等實證方法，協助學生建立健康的心理狀態。',
                'feature_img' => 'counseling/personal-counseling.jpg',
                'counseling_mode' => 'both',
                'session_duration' => 60,
                'total_sessions' => 1,
                'allow_reschedule' => true,
                'status' => 'active',
            ],
            [
                'name' => '綜合諮商服務套餐',
                'code' => 'COUNSELING-INFO-COMBO-004',
                'description' => '結合學業、職涯、心理三方面的全方位諮商服務',
                'details' => '提供完整的學生輔導服務，包含學業指導、職涯規劃、心理支持等多元化諮商內容。適合需要全面性支持的學生。',
                'feature_img' => 'counseling/combo-counseling.jpg',
                'counseling_mode' => 'both',
                'session_duration' => 75,
                'total_sessions' => 3,
                'allow_reschedule' => true,
                'status' => 'active',
            ],
        ];

        $counselingInfos = [];
        foreach ($counselingInfosData as $index => $counselingData) {
            $counselingData['product_id'] = $products[$index]->id;
            
            $counselingInfo = CounselingInfo::firstOrCreate(
                ['code' => $counselingData['code']],
                $counselingData
            );

            $counselingInfos[] = $counselingInfo;
        }

        return $counselingInfos;
    }

    /**
     * 建立諮商師關聯
     */
    private function createCounselorRelations(array $counselingInfos): void
    {
        // 取得具有諮商師角色的會員
        $counselorRole = Role::where('slug', 'counselor')->first();

        if (!$counselorRole) {
            $this->command->warn('⚠️ 找不到諮商師角色，請確保已執行 DatabaseSeeder');
            return;
        }

        $counselors = Member::whereHas('roles', function($q) use ($counselorRole) {
            $q->where('roles.id', $counselorRole->id);
        })->where('status', true)->get();

        if ($counselors->isEmpty()) {
            $this->command->warn('⚠️ 找不到具有諮商師角色的會員，請確保已執行 MemberSeeder');
            return;
        }

        // 為每個諮商服務分配諮商師
        foreach ($counselingInfos as $counselingInfo) {
            // 隨機選擇1-3個諮商師作為該服務的諮商師
            $count = min(3, $counselors->count());
            $selectedCounselors = $counselors->random($count);

            foreach ($selectedCounselors as $index => $counselor) {
                // 檢查是否已存在關聯
                $exists = \DB::table('counseling_info_counselors')
                    ->where('counseling_info_id', $counselingInfo->id)
                    ->where('counselor_id', $counselor->id)
                    ->exists();

                if (!$exists) {
                    \DB::table('counseling_info_counselors')->insert([
                        'counseling_info_id' => $counselingInfo->id,
                        'counselor_id' => $counselor->id,
                        'is_primary' => $index === 0, // 第一個設為主要諮商師
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info("✓ 已為 " . count($counselingInfos) . " 個諮商服務分配諮商師");
    }

    /**
     * 建立預約範例
     */
    private function createSampleAppointments(array $counselingInfos): void
    {
        if (empty($counselingInfos)) {
            $this->command->warn('⚠️ 缺少諮商資訊，無法建立預約範例');
            return;
        }

        // 取得具有學生角色的會員
        $studentRole = Role::where('slug', 'student')->first();

        if (!$studentRole) {
            $this->command->warn('⚠️ 找不到學生角色，請確保已執行 DatabaseSeeder');
            return;
        }

        $students = Member::whereHas('roles', function($q) use ($studentRole) {
            $q->where('roles.id', $studentRole->id);
        })->where('status', true)->get();

        if ($students->isEmpty()) {
            $this->command->warn('⚠️ 找不到具有學生角色的會員，請確保已執行 MemberSeeder');
            return;
        }

        // 隨機選擇一些學生
        $selectedStudents = $students->random(min(3, $students->count()));

        // 先創建一些 OrderItem 作為範例（實際應該由購買流程產生）
        $orderItems = $this->createSampleOrderItems($selectedStudents, $counselingInfos);

        // 為每個諮商服務創建範例預約
        foreach ($counselingInfos as $index => $counselingInfo) {
            if (!isset($orderItems[$index])) continue;

            // 從該諮商服務的諮商師中隨機選擇一位
            $availableCounselors = \DB::table('counseling_info_counselors')
                ->where('counseling_info_id', $counselingInfo->id)
                ->pluck('counselor_id');

            if ($availableCounselors->isEmpty()) continue;

            $counselorId = $availableCounselors->random();
            $student = $selectedStudents[$index % $selectedStudents->count()];

            $appointmentData = [
                'order_item_id' => $orderItems[$index]->id,
                'counseling_info_id' => $counselingInfo->id,
                'student_id' => $student->id,
                'counselor_id' => $counselorId,
                'duration' => $counselingInfo->session_duration,
                'method' => 'online',
                'is_urgent' => false,
            ];

            // 根據服務類型設定不同的預約內容
            switch ($index % 3) {
                case 0:
                    $appointmentData = array_merge($appointmentData, [
                        'title' => '期中考準備策略諮商',
                        'description' => '希望諮詢如何有效準備期中考試，改善學習方法。',
                        'status' => 'pending',
                        'type' => 'academic',
                        'preferred_datetime' => Carbon::now()->addDays(3)->setHour(14)->setMinute(0),
                    ]);
                    break;
                case 1:
                    $appointmentData = array_merge($appointmentData, [
                        'title' => '畢業後職涯規劃諮詢',
                        'description' => '想了解自己的職業興趣和未來發展方向。',
                        'status' => 'confirmed',
                        'type' => 'career',
                        'preferred_datetime' => Carbon::now()->addDays(2)->setHour(16)->setMinute(0),
                        'confirmed_datetime' => Carbon::now()->addDays(2)->setHour(16)->setMinute(0),
                        'method' => 'offline',
                        'location' => '諮商室A',
                    ]);
                    break;
                case 2:
                    $appointmentData = array_merge($appointmentData, [
                        'title' => '考試焦慮處理',
                        'description' => '最近考試總是很緊張，影響表現，希望能學習放鬆技巧。',
                        'status' => 'completed',
                        'type' => 'personal',
                        'preferred_datetime' => Carbon::now()->subDays(3)->setHour(15)->setMinute(0),
                        'confirmed_datetime' => Carbon::now()->subDays(3)->setHour(15)->setMinute(0),
                        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
                        'counselor_notes' => '學員學習了深呼吸和正念技巧，建議持續練習。',
                        'student_feedback' => '諮商師很專業，學到了很多實用的放鬆方法。',
                        'rating' => 5,
                    ]);
                    break;
            }

            CounselingAppointment::create($appointmentData);
        }

        $this->command->info("✓ 已建立 " . count($orderItems) . " 個諮商預約範例");
    }

    /**
     * 建立範例訂單項目
     */
    private function createSampleOrderItems($students, array $counselingInfos): array
    {
        $orderItems = [];

        foreach ($students as $index => $student) {
            if (isset($counselingInfos[$index])) {
                $counselingInfo = $counselingInfos[$index];
                $product = Product::find($counselingInfo->product_id);
                
                if (!$product) continue;
                
                // 先創建訂單
                $order = Order::create([
                    'member_id' => $student->id,
                    'code' => 'ORD-' . strtoupper(uniqid()),
                    'total' => $product->discount_price ?: $product->regular_price,
                    'currency' => 'TWD',
                    'status' => 'completed',
                ]);
                
                // 再創建訂單項目
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $counselingInfo->product_id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'price' => $product->discount_price ?: $product->regular_price,
                ]);

                $orderItems[] = $orderItem;
            }
        }

        return $orderItems;
    }
}