<?php

namespace Database\Seeders;

use App\Models\NoticeType;
use Illuminate\Database\Seeder;

class NoticeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '最新通知',
                'slug' => 'latest',
                'note' => 'A latest notice type.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '優惠通知',
                'slug' => 'promotion',
                'note' => 'A promotion notice type.',
                'sort' => 2,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            NoticeType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
