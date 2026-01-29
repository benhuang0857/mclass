<?php

namespace Database\Seeders;

use App\Models\SchoolType;
use Illuminate\Database\Seeder;

class SchoolTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '台灣大學',
                'slug' => 'ntu',
                'note' => 'National Taiwan University',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '清華大學',
                'slug' => 'nthu',
                'note' => 'National Tsing Hua University',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '交通大學',
                'slug' => 'nctu',
                'note' => 'National Chiao Tung University',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '成功大學',
                'slug' => 'ncku',
                'note' => 'National Cheng Kung University',
                'sort' => 4,
                'status' => true,
            ],
            [
                'name' => '其他',
                'slug' => 'other',
                'note' => 'Other schools',
                'sort' => 99,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            SchoolType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
