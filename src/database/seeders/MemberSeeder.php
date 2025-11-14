<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\Role;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('開始建立會員資料...');

        // 取得所有角色
        $roles = Role::all()->keyBy('slug');

        if ($roles->isEmpty()) {
            $this->command->error('⚠️ 沒有找到角色資料，請先執行 DatabaseSeeder 建立角色');
            return;
        }

        // 建立各種角色的會員
        $this->createStudents($roles);
        $this->createPlanners($roles);
        $this->createCounselors($roles);
        $this->createAnalysts($roles);
        $this->createTeachers($roles);
        $this->createSales($roles);

        $this->command->info('✅ 會員資料建立完成！');
    }

    /**
     * 建立學生會員
     */
    private function createStudents($roles): void
    {
        $students = [
            [
                'nickname' => '王小明',
                'account' => 'student001',
                'email' => 'student001@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => 'John Smith',
                'account' => 'student002',
                'email' => 'student002@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => '李美華',
                'account' => 'student003',
                'email' => 'student003@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => '陳志豪',
                'account' => 'student004',
                'email' => 'student004@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
        ];

        foreach ($students as $studentData) {
            $member = Member::firstOrCreate(
                ['email' => $studentData['email']],
                $studentData
            );

            // 分配學生角色
            if ($roles->has('student')) {
                $member->roles()->syncWithoutDetaching([$roles['student']->id]);
            }

            $this->command->info("✓ 學生: {$member->nickname} ({$member->email})");
        }
    }

    /**
     * 建立規劃師會員
     */
    private function createPlanners($roles): void
    {
        $planners = [
            [
                'nickname' => '張規劃',
                'account' => 'planner001',
                'email' => 'planner001@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => 'Emily Chen',
                'account' => 'planner002',
                'email' => 'planner002@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
        ];

        foreach ($planners as $plannerData) {
            $member = Member::firstOrCreate(
                ['email' => $plannerData['email']],
                $plannerData
            );

            // 分配規劃師角色
            if ($roles->has('planner')) {
                $member->roles()->syncWithoutDetaching([$roles['planner']->id]);
            }

            $this->command->info("✓ 規劃師: {$member->nickname} ({$member->email})");
        }
    }

    /**
     * 建立諮商師會員
     */
    private function createCounselors($roles): void
    {
        $counselors = [
            [
                'nickname' => '林諮商',
                'account' => 'counselor001',
                'email' => 'counselor001@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => 'David Lee',
                'account' => 'counselor002',
                'email' => 'counselor002@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => '黃心理',
                'account' => 'counselor003',
                'email' => 'counselor003@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
        ];

        foreach ($counselors as $counselorData) {
            $member = Member::firstOrCreate(
                ['email' => $counselorData['email']],
                $counselorData
            );

            // 分配諮商師角色
            if ($roles->has('counselor')) {
                $member->roles()->syncWithoutDetaching([$roles['counselor']->id]);
            }

            $this->command->info("✓ 諮商師: {$member->nickname} ({$member->email})");
        }
    }

    /**
     * 建立分析師會員
     */
    private function createAnalysts($roles): void
    {
        $analysts = [
            [
                'nickname' => '吳分析',
                'account' => 'analyst001',
                'email' => 'analyst001@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => 'Sarah Wang',
                'account' => 'analyst002',
                'email' => 'analyst002@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
        ];

        foreach ($analysts as $analystData) {
            $member = Member::firstOrCreate(
                ['email' => $analystData['email']],
                $analystData
            );

            // 分配分析師角色
            if ($roles->has('analyst')) {
                $member->roles()->syncWithoutDetaching([$roles['analyst']->id]);
            }

            $this->command->info("✓ 分析師: {$member->nickname} ({$member->email})");
        }
    }

    /**
     * 建立教師會員
     */
    private function createTeachers($roles): void
    {
        $teachers = [
            [
                'nickname' => '鄭老師',
                'account' => 'teacher001',
                'email' => 'teacher001@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
            [
                'nickname' => 'Michael Johnson',
                'account' => 'teacher002',
                'email' => 'teacher002@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
        ];

        foreach ($teachers as $teacherData) {
            $member = Member::firstOrCreate(
                ['email' => $teacherData['email']],
                $teacherData
            );

            // 分配教師角色
            if ($roles->has('teacher')) {
                $member->roles()->syncWithoutDetaching([$roles['teacher']->id]);
            }

            $this->command->info("✓ 教師: {$member->nickname} ({$member->email})");
        }
    }

    /**
     * 建立業務會員
     */
    private function createSales($roles): void
    {
        $salesMembers = [
            [
                'nickname' => '許業務',
                'account' => 'sales001',
                'email' => 'sales001@example.com',
                'email_valid' => true,
                'password' => bcrypt('password'),
                'status' => true,
            ],
        ];

        foreach ($salesMembers as $salesData) {
            $member = Member::firstOrCreate(
                ['email' => $salesData['email']],
                $salesData
            );

            // 分配業務角色
            if ($roles->has('sales')) {
                $member->roles()->syncWithoutDetaching([$roles['sales']->id]);
            }

            $this->command->info("✓ 業務: {$member->nickname} ({$member->email})");
        }
    }
}
