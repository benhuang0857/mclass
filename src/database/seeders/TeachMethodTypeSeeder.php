<?php

namespace Database\Seeders;

use App\Models\TeachMethodType;
use Illuminate\Database\Seeder;

class TeachMethodTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '剪輯課程',
                'slug' => 'edited_course',
                'note' => 'A Edited Course teach method type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '直播課程',
                'slug' => 'live_course',
                'note' => 'A Live Course teach method type.',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '家教課程等',
                'slug' => 'tutoring_course',
                'note' => 'A Tutoring Course teach method type.',
                'sort' => 3,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            TeachMethodType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
