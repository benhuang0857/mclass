<?php

namespace Database\Seeders;

use App\Models\ClubCourse;
use App\Models\ClubCourseInfo;
use App\Models\Product;
use App\Models\Member;
use App\Models\User;
use App\Models\LangType;
use App\Models\LevelType;
use App\Models\CourseInfoType;
use App\Models\TeachMethodType;
use App\Models\CourseStatusType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ClubCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 首先建立必要的基礎資料
        $this->createProducts();
        $this->createMembers();
        
        // 建立課程資訊
        $courseInfos = $this->createClubCourseInfos();
        
        // 為每個課程資訊建立實際課程
        foreach ($courseInfos as $courseInfo) {
            $this->createClubCoursesForInfo($courseInfo);
        }
    }

    /**
     * 建立產品資料
     */
    private function createProducts(): void
    {
        $products = [
            [
                'name' => '英語會話入門課程',
                'code' => 'PROD-ENG-001',
                'feature_img' => 'products/english-conversation.jpg',
                'regular_price' => 2500.00,
                'discount_price' => 2200.00,
                'limit_enrollment' => true,
                'max_enrollment' => 20,
                'stock' => 20,
                'is_series' => true,
                'elective' => false,
                'is_visible_to_specific_students' => false,
                'status' => 'published',
            ],
            [
                'name' => '商用英語進階班',
                'code' => 'PROD-BUS-002',
                'feature_img' => 'products/business-english.jpg',
                'regular_price' => 3500.00,
                'discount_price' => 3200.00,
                'limit_enrollment' => true,
                'max_enrollment' => 15,
                'stock' => 15,
                'is_series' => true,
                'elective' => false,
                'is_visible_to_specific_students' => false,
                'status' => 'published',
            ],
            [
                'name' => '普通話發音矯正',
                'code' => 'PROD-MAN-003',
                'feature_img' => 'products/mandarin-pronunciation.jpg',
                'regular_price' => 2000.00,
                'discount_price' => 1800.00,
                'limit_enrollment' => true,
                'max_enrollment' => 12,
                'stock' => 12,
                'is_series' => true,
                'elective' => false,
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
     * 建立會員資料
     */
    private function createMembers(): void
    {
        $members = [
            [
                'nickname' => '王小明',
                'account' => 'wang001',
                'email' => 'wang@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => 'John Smith',
                'account' => 'john002',
                'email' => 'john@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => '李美華',
                'account' => 'li003',
                'email' => 'li@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
        ];

        foreach ($members as $memberData) {
            Member::firstOrCreate(
                ['email' => $memberData['email']],
                $memberData
            );
        }
    }

    /**
     * 建立課程資訊
     */
    private function createClubCourseInfos(): array
    {
        $products = Product::all();
        $languages = LangType::all();
        $levels = LevelType::all();
        $courseInfoTypes = CourseInfoType::all();
        $teachMethods = TeachMethodType::all();
        $members = Member::all();
        $users = User::all();

        $courseInfosData = [
            [
                'name' => '基礎英語會話班',
                'code' => 'ENG-CONV-001',
                'description' => '從零開始學習英語會話，適合完全初學者',
                'details' => '本課程包含基礎發音、日常對話、文法介紹等內容。透過互動式教學，讓學員能夠自信地使用英語進行日常溝通。',
                'feature_img' => 'courses/english-conversation.jpg',
                'teaching_mode' => 'online',
                'schedule_display' => '每週二、四 19:00-20:30',
                'is_periodic' => true,
                'total_sessions' => 12,
                'allow_replay' => true,
                'status' => 'published',
            ],
            [
                'name' => '商用英語進階課程',
                'code' => 'ENG-BUS-002',
                'description' => '提升職場英語溝通技巧，包含簡報、會議、談判等場景',
                'details' => '專為職場人士設計，涵蓋商業書信、會議討論、簡報技巧、談判策略等實用內容。',
                'feature_img' => 'courses/business-english.jpg',
                'teaching_mode' => 'hybrid',
                'schedule_display' => '每週三 20:00-21:30',
                'is_periodic' => true,
                'total_sessions' => 16,
                'allow_replay' => true,
                'status' => 'published',
            ],
            [
                'name' => '普通話發音特訓班',
                'code' => 'MAN-PRON-003',
                'description' => '專業普通話發音指導，糾正發音問題',
                'details' => '針對常見的發音問題進行針對性訓練，包含聲調練習、捲舌音、前後鼻音等。',
                'feature_img' => 'courses/mandarin-pronunciation.jpg',
                'teaching_mode' => 'offline',
                'schedule_display' => '每週六 14:00-16:00',
                'is_periodic' => true,
                'total_sessions' => 8,
                'allow_replay' => false,
                'status' => 'published',
            ],
        ];

        $courseInfos = [];
        foreach ($courseInfosData as $index => $courseData) {
            $courseData['product_id'] = $products[$index % $products->count()]->id;
            
            $courseInfo = ClubCourseInfo::firstOrCreate(
                ['code' => $courseData['code']],
                $courseData
            );

            // 建立關聯關係
            if ($languages->count() > 0) {
                $courseInfo->languages()->sync([$languages->random()->id]);
            }
            
            if ($levels->count() > 0) {
                $courseInfo->levels()->sync([$levels->random()->id]);
            }
            
            if ($courseInfoTypes->count() > 0) {
                $courseInfo->courseInfoTypes()->sync([$courseInfoTypes->random()->id]);
            }
            
            if ($teachMethods->count() > 0) {
                $courseInfo->teachMethods()->sync([$teachMethods->random()->id]);
            }
            
            if ($members->count() > 0) {
                $courseInfo->teachers()->sync([$members->random()->id]);
            }
            
            if ($users->count() > 0) {
                $courseInfo->sysmans()->sync([$users->first()->id]);
            }

            $courseInfos[] = $courseInfo;
        }

        return $courseInfos;
    }

    /**
     * 為課程資訊建立實際課程
     */
    private function createClubCoursesForInfo(ClubCourseInfo $courseInfo): void
    {
        $courseStatusTypes = CourseStatusType::all();
        $startDate = Carbon::now()->addDays(7); // 一週後開始
        
        // 為每個課程資訊建立多個課程實例
        for ($i = 0; $i < $courseInfo->total_sessions; $i++) {
            $sessionDate = $startDate->copy()->addWeeks($i);
            
            // 根據課程資訊設定時間
            $startTime = $sessionDate->copy()->setTime(19, 0, 0); // 預設晚上7點
            $endTime = $startTime->copy()->addMinutes(90); // 1.5小時課程
            
            // 調整不同課程的時間
            if ($courseInfo->code === 'ENG-BUS-002') {
                // 商用英語進階課程改成與基礎英語會話班相同時間 (19:00-20:30)
                $startTime->setTime(19, 0, 0);
                $endTime = $startTime->copy()->addMinutes(90);
            } elseif ($courseInfo->code === 'MAN-PRON-003') {
                $startTime->setTime(14, 0, 0);
                $endTime = $startTime->copy()->addHours(2);
            }

            $clubCourse = ClubCourse::create([
                'course_id' => $courseInfo->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'link' => $courseInfo->teaching_mode === 'online' ? 'https://zoom.us/j/123456789' : null,
                'location' => $courseInfo->teaching_mode === 'offline' ? '台北市信義區教室A' : null,
                'trial' => $i === 0, // 第一堂課設為試聽
                'sort' => $i + 1,
            ]);

            // 設定課程狀態
            if ($courseStatusTypes->count() > 0) {
                $status = $i === 0 ? 
                    $courseStatusTypes->where('slug', 'coming_soon')->first() : 
                    $courseStatusTypes->random();
                
                if ($status) {
                    $clubCourse->courseStatusTypes()->sync([$status->id]);
                }
            }
        }
    }
}