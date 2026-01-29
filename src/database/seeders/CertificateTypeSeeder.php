<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use Illuminate\Database\Seeder;

class CertificateTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'TOEIC',
                'slug' => 'toeic',
                'note' => 'Test of English for International Communication',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => 'TOEFL',
                'slug' => 'toefl',
                'note' => 'Test of English as a Foreign Language',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => 'IELTS',
                'slug' => 'ielts',
                'note' => 'International English Language Testing System',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => 'JLPT',
                'slug' => 'jlpt',
                'note' => 'Japanese Language Proficiency Test',
                'sort' => 4,
                'status' => true,
            ],
            [
                'name' => 'HSK',
                'slug' => 'hsk',
                'note' => 'Hanyu Shuiping Kaoshi (Chinese Proficiency Test)',
                'sort' => 5,
                'status' => true,
            ],
            [
                'name' => 'DELE',
                'slug' => 'dele',
                'note' => 'Diplomas de Español como Lengua Extranjera',
                'sort' => 6,
                'status' => true,
            ],
            [
                'name' => '其他',
                'slug' => 'other',
                'note' => 'Other certificates',
                'sort' => 99,
                'status' => true,
            ],
        ];

        foreach ($types as $type) {
            CertificateType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
