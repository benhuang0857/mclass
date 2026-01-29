<?php

namespace Database\Seeders;

use App\Models\DepartmentType;
use Illuminate\Database\Seeder;

class DepartmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '資訊工程',
                'slug' => 'computer_science',
                'note' => 'Computer Science',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '電機工程',
                'slug' => 'electrical_engineering',
                'note' => 'Electrical Engineering',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '機械工程',
                'slug' => 'mechanical_engineering',
                'note' => 'Mechanical Engineering',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '企業管理',
                'slug' => 'business_administration',
                'note' => 'Business Administration',
                'sort' => 4,
                'status' => true,
            ],
            [
                'name' => '經濟學',
                'slug' => 'economics',
                'note' => 'Economics',
                'sort' => 5,
                'status' => true,
            ],
            [
                'name' => '外國語文',
                'slug' => 'foreign_languages',
                'note' => 'Foreign Languages',
                'sort' => 6,
                'status' => true,
            ],
            [
                'name' => '其他',
                'slug' => 'other',
                'note' => 'Other departments',
                'sort' => 99,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            DepartmentType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
