<?php

namespace Database\Seeders;

use App\Models\CourseStatusType;
use Illuminate\Database\Seeder;

class CourseStatusTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
