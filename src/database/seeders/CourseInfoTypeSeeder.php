<?php

namespace Database\Seeders;

use App\Models\CourseInfoType;
use Illuminate\Database\Seeder;

class CourseInfoTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '綜合課',
                'slug' => 'comprehensive_course',
                'note' => 'A Comprehensive Course course info type.',
                'sort' => 3,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            CourseInfoType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
