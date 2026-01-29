<?php

namespace Database\Seeders;

use App\Models\GoalType;
use Illuminate\Database\Seeder;

class GoalTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '升學考試',
                'slug' => 'academic_exam',
                'note' => 'Preparing for academic examinations',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '職場升遷',
                'slug' => 'career_advancement',
                'note' => 'Career advancement and promotion',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '出國留學',
                'slug' => 'study_abroad',
                'note' => 'Study abroad',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '興趣培養',
                'slug' => 'hobby',
                'note' => 'Personal interest and hobby',
                'sort' => 4,
                'status' => true,
            ],
            [
                'name' => '證照取得',
                'slug' => 'certification',
                'note' => 'Obtaining professional certifications',
                'sort' => 5,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            GoalType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
