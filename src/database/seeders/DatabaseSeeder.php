<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 建立測試用戶
        $this->createUserIfNotExists([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);

        // 建立基礎類型資料
        $this->call([
            RoleSeeder::class,
            NoticeTypeSeeder::class,
            LangTypeSeeder::class,
            LevelTypeSeeder::class,
            TeachMethodTypeSeeder::class,
            CourseInfoTypeSeeder::class,
            CourseStatusTypeSeeder::class,
            ReferralSourceTypeSeeder::class,
            GoalTypeSeeder::class,
            PurposeTypeSeeder::class,
            EducationTypeSeeder::class,
            SchoolTypeSeeder::class,
            DepartmentTypeSeeder::class,
            CertificateTypeSeeder::class,
            SlideshowTypeSeeder::class,
        ]);

        // 建立菜單資料（需要角色資料）
        $this->call(MenuSeeder::class);

        // 建立通知資料（需要通知類型資料）
        $this->call(NoticeSeeder::class);

        // 建立會員資料（必須在課程之前）
        $this->call(MemberSeeder::class);

        // 建立課程相關的測試資料
        $this->call([
            ClubCourseSeeder::class,
            CounselingSeeder::class,
            FlipCourseSeeder::class,
        ]);
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
}
