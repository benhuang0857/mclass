<?php

namespace Database\Seeders;

use App\Models\LangType;
use Illuminate\Database\Seeder;

class LangTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '英文',
                'slug' => 'english',
                'note' => 'A english lang type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '普通話',
                'slug' => 'mandarin',
                'note' => 'A mandarin lang type.',
                'sort' => 2,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            LangType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
