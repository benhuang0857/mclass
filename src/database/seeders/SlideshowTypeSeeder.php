<?php

namespace Database\Seeders;

use App\Models\SlideshowType;
use Illuminate\Database\Seeder;

class SlideshowTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '首頁輪播',
                'slug' => 'home_banner',
                'note' => 'Home page banner slideshow',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '課程推薦',
                'slug' => 'course_recommendation',
                'note' => 'Course recommendation slideshow',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '活動宣傳',
                'slug' => 'event_promotion',
                'note' => 'Event promotion slideshow',
                'sort' => 3,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            SlideshowType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
