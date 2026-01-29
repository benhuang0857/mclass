<?php

namespace Database\Seeders;

use App\Models\LevelType;
use Illuminate\Database\Seeder;

class LevelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '初階',
                'slug' => 'beginner',
                'note' => 'A beginner level type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '中階',
                'slug' => 'intermediate',
                'note' => 'A Intermediate lang type.',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '高階',
                'slug' => 'advanced',
                'note' => 'A advanced lang type.',
                'sort' => 3,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            LevelType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
