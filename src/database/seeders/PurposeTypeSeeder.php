<?php

namespace Database\Seeders;

use App\Models\PurposeType;
use Illuminate\Database\Seeder;

class PurposeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '工作需求',
                'slug' => 'work_requirement',
                'note' => 'Required for work',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '個人興趣',
                'slug' => 'personal_interest',
                'note' => 'Personal interest',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '學業要求',
                'slug' => 'academic_requirement',
                'note' => 'Required for academic purposes',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '旅遊需求',
                'slug' => 'travel',
                'note' => 'For travel purposes',
                'sort' => 4,
                'status' => true,
            ],
            [
                'name' => '移民準備',
                'slug' => 'immigration',
                'note' => 'Immigration preparation',
                'sort' => 5,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            PurposeType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
