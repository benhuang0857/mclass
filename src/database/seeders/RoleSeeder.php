<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
            [
                'name' => '規劃師',
                'slug' => 'planner',
                'note' => 'A planner managing flip course workflow and assignments.',
                'sort' => 5,
                'status' => true,
            ],
            [
                'name' => '諮商師',
                'slug' => 'counselor',
                'note' => 'A counselor providing learning guidance and strategy.',
                'sort' => 6,
                'status' => true,
            ],
            [
                'name' => '分析師',
                'slug' => 'analyst',
                'note' => 'An analyst evaluating learning outcomes and performance.',
                'sort' => 7,
                'status' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
