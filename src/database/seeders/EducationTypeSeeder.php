<?php

namespace Database\Seeders;

use App\Models\EducationType;
use Illuminate\Database\Seeder;

class EducationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '高中',
                'slug' => 'high_school',
                'note' => 'High school education',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '專科',
                'slug' => 'associate',
                'note' => 'Associate degree',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '學士',
                'slug' => 'bachelor',
                'note' => 'Bachelor degree',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '碩士',
                'slug' => 'master',
                'note' => 'Master degree',
                'sort' => 4,
                'status' => true,
            ],
            [
                'name' => '博士',
                'slug' => 'phd',
                'note' => 'Doctoral degree',
                'sort' => 5,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            EducationType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
