<?php

namespace Database\Seeders;

use App\Models\ReferralSourceType;
use Illuminate\Database\Seeder;

class ReferralSourceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => '朋友介紹',
                'slug' => 'friend_referral',
                'note' => 'Referred by a friend',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '網路搜尋',
                'slug' => 'online_search',
                'note' => 'Found through online search',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '社群媒體',
                'slug' => 'social_media',
                'note' => 'Found through social media',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '廣告',
                'slug' => 'advertisement',
                'note' => 'Found through advertisement',
                'sort' => 4,
                'status' => true,
            ],
            [
                'name' => '其他',
                'slug' => 'other',
                'note' => 'Other sources',
                'sort' => 5,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            ReferralSourceType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
