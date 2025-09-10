<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\NoticeType;
use App\Models\LangType;
use App\Models\LevelType;
use App\Models\TeachMethodType;
use App\Models\CourseInfoType;
use App\Models\CourseStatusType;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createUserIfNotExists([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);

        $this->createRoles();
        $this->createNoticeTypes();
        $this->createLangTypes();
        $this->createLevelTypes();
        $this->createTeachMethodTypes();
        $this->createCourseInfoTypes();
        $this->createCourseStatusTypes();
        
        // 建立課程相關的假資料
        $this->call(ClubCourseSeeder::class);
        $this->call(CounselingSeeder::class);
    }

    /**
     * Create a user if it does not already exist.
     *
     * @param array $userData
     * @return void
     */
    protected function createUserIfNotExists(array $userData): void
    {
        $email = $userData['email'];

        if (!User::where('email', $email)->exists()) {
            User::factory()->create($userData);
        } else {
            $this->command->info("User with email {$email} already exists. Skipping creation.");
        }
    }

    /**
     * Create predefined roles if they do not already exist.
     *
     * @return void
     */
    protected function createRoles(): void
    {
        $roles = [
            [
                'name' => '學員',
                'slug' => 'student',
                'note' => 'A student enrolled in courses.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '教師',
                'slug' => 'teacher',
                'note' => 'A teacher delivering course content.',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '助教',
                'slug' => 'assistant',
                'note' => 'An assistant helping students and teachers.',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '業務',
                'slug' => 'sales',
                'note' => 'A sales representative managing courses.',
                'sort' => 4,
                'status' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']], // Check for existing role by slug
                $role // Attributes to create if not exists
            );
        }
    }

    /**
     * Create predefined Notice Types if they do not already exist.
     *
     * @return void
     */
    protected function createNoticeTypes(): void
    {
        $types = [
            [
                'name' => '最新通知',
                'slug' => 'latest',
                'note' => 'A latest notice type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '優惠通知',
                'slug' => 'promotion',
                'note' => 'A promotion notice type.',
                'sort' => 2,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            NoticeType::firstOrCreate(
                ['slug' => $type['slug']], // Check for existing role by slug
                $type // Attributes to create if not exists
            );
        }
    }

    /**
     * Create predefined Lang Types if they do not already exist.
     *
     * @return void
     */
    protected function createLangTypes(): void
    {
        $types = [
            [
                'name' => '英文',
                'slug' => 'english',
                'note' => 'A english lang type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '普通話',
                'slug' => 'mandarin',
                'note' => 'A mandarin lang type.',
                'sort' => 2,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            LangType::firstOrCreate(
                ['slug' => $type['slug']], // Check for existing role by slug
                $type // Attributes to create if not exists
            );
        }
    }

    /**
     * Create predefined Level Types if they do not already exist.
     *
     * @return void
     */
    protected function createLevelTypes(): void
    {
        $types = [
            [
                'name' => '初階',
                'slug' => 'beginner',
                'note' => 'A beginner level type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '中階',
                'slug' => 'intermediate',
                'note' => 'A Intermediate lang type.',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '高階',
                'slug' => 'advanced',
                'note' => 'A advanced lang type.',
                'sort' => 3,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            LevelType::firstOrCreate(
                ['slug' => $type['slug']], // Check for existing role by slug
                $type // Attributes to create if not exists
            );
        }
    }

    /**
     * Create predefined Teach Method Types if they do not already exist.
     *
     * @return void
     */
    protected function createTeachMethodTypes(): void
    {
        $types = [
            [
                'name' => '剪輯課程',
                'slug' => 'edited_course',
                'note' => 'A Edited Course teach method type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '直播課程',
                'slug' => 'live_course',
                'note' => 'A Live Course teach method type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '家教課程等',
                'slug' => 'tutoring_course',
                'note' => 'A Tutoring Course teach method type.',
                'sort' => 1,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            TeachMethodType::firstOrCreate(
                ['slug' => $type['slug']], // Check for existing role by slug
                $type // Attributes to create if not exists
            );
        }
    }

    /**
     * Create predefined Course Info Types if they do not already exist.(課程分類)
     *
     * @return void
     */
    protected function createCourseInfoTypes(): void
    {
        $types = [
            [
                'name' => '語言課',
                'slug' => 'language_course',
                'note' => 'A Language Course course info type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '發音課',
                'slug' => 'pronunciation_course',
                'note' => 'A Pronunciation Course course info type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '綜合課',
                'slug' => 'comprehensive_course',
                'note' => 'A Comprehensive Course course info type.',
                'sort' => 1,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            CourseInfoType::firstOrCreate(
                ['slug' => $type['slug']], // Check for existing role by slug
                $type // Attributes to create if not exists
            );
        }
    }

    /**
     * Create predefined Course Status Types if they do not already exist.
     *
     * @return void
     */
    protected function createCourseStatusTypes(): void
    {
        $types = [
            [
                'name' => '即將開課',
                'slug' => 'coming_soon',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '臨時停課',
                'slug' => 'suspension ',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '課程關閉',
                'slug' => 'closed',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '課程完成',
                'slug' => 'completed',
                'sort' => 4,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            CourseStatusType::firstOrCreate(
                ['slug' => $type['slug']], // Check for existing role by slug
                $type // Attributes to create if not exists
            );
        }
    }
}
