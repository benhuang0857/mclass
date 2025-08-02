<?php

namespace Database\Seeders;

use App\Models\ZoomCredential;
use Illuminate\Database\Seeder;

class ZoomCredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 創建第一個 Zoom 憑證（需要手動填入真實憑證）
        ZoomCredential::create([
            'name' => 'Primary Zoom Account',
            'account_id' => '0bIfdcUUQVKQ_prfy7Ar0A', // 替換為你的真實憑證
            'client_id' => 'SXlCZcqZQ02Aa6guhn4k8g', // 替換為你的真實憑證
            'client_secret' => 'WJBeVncIPg8JxGe0H9MX2PmNOa20kwzV', // 替換為你的真實憑證
            'email' => 'primary@example.com',
            'is_active' => true,
            'max_concurrent_meetings' => 2,
            'current_meetings' => 0,
            'settings' => [
                'description' => '主要 Zoom 帳號',
                'priority' => 1
            ]
        ]);

        // 創建第二個測試憑證（需要你提供真實憑證）
        ZoomCredential::create([
            'name' => 'Secondary Zoom Account',
            'account_id' => 'your_second_account_id',
            'client_id' => 'your_second_client_id',
            'client_secret' => 'your_second_client_secret',
            'email' => 'secondary@example.com',
            'is_active' => false, // 預設為停用，等你填入真實憑證後再啟用
            'max_concurrent_meetings' => 2,
            'current_meetings' => 0,
            'settings' => [
                'description' => '備用 Zoom 帳號',
                'priority' => 2
            ]
        ]);
    }
}
