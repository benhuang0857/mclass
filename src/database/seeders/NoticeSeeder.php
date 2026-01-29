<?php

namespace Database\Seeders;

use App\Models\Notice;
use App\Models\NoticeType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class NoticeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 取得通知類型
        $latestType = NoticeType::where('slug', 'latest')->first();
        $promotionType = NoticeType::where('slug', 'promotion')->first();

        if (!$latestType || !$promotionType) {
            $this->command->warn('⚠️ 找不到通知類型，請先執行 NoticeTypeSeeder');
            return;
        }

        $notices = [
            // 最新通知
            [
                'title' => '平台系統維護公告',
                'feature_img' => 'https://via.placeholder.com/800x400/3F51B5/FFFFFF?text=System+Maintenance',
                'notice_type_id' => $latestType->id,
                'body' => '<p>親愛的用戶您好：</p><p>本平台將於 <strong>' . Carbon::now()->addDays(7)->format('Y年m月d日') . ' 凌晨 2:00 - 6:00</strong> 進行系統維護作業。</p><p>維護期間可能會暫時無法使用部分功能，造成不便敬請見諒。</p><p>維護內容包括：</p><ul><li>系統效能優化</li><li>新功能部署</li><li>安全性更新</li></ul><p>如有任何疑問，請聯繫客服人員。</p>',
                'status' => true,
            ],
            [
                'title' => '新課程上架通知 - 商用英語進階班',
                'feature_img' => 'https://via.placeholder.com/800x400/4CAF50/FFFFFF?text=New+Course+Available',
                'notice_type_id' => $latestType->id,
                'body' => '<p>好消息！我們最新推出的 <strong>商用英語進階班</strong> 正式上架了！</p><p><strong>課程特色：</strong></p><ul><li>專業外師授課</li><li>小班制教學（最多15人）</li><li>實戰商業場景模擬</li><li>提供課程錄影回放</li></ul><p><strong>開課時間：</strong>' . Carbon::now()->addDays(14)->format('Y年m月d日') . '</p><p>立即報名享早鳥優惠！</p>',
                'status' => true,
            ],
            [
                'title' => '翻轉課程服務全新上線',
                'feature_img' => 'https://via.placeholder.com/800x400/FF9800/FFFFFF?text=Flip+Course+Launch',
                'notice_type_id' => $latestType->id,
                'body' => '<p>我們很高興地宣布，<strong>翻轉課程服務</strong>正式上線！</p><p><strong>什麼是翻轉課程？</strong></p><p>翻轉課程採用全新的學習模式，由專業團隊為您打造個人化學習計畫：</p><ol><li><strong>諮商師</strong> - 評估您的學習需求並制定策略</li><li><strong>規劃師</strong> - 安排合適的課程與任務</li><li><strong>分析師</strong> - 定期評估學習成果</li></ol><p>循環優化，讓學習更有效率！</p><p><a href="/courses/flip">了解更多 →</a></p>',
                'status' => true,
            ],
            [
                'title' => '學員成功案例分享 - 從零基礎到英語流利',
                'feature_img' => 'https://via.placeholder.com/800x400/9C27B0/FFFFFF?text=Success+Story',
                'notice_type_id' => $latestType->id,
                'body' => '<p>今天要和大家分享學員王小明的學習故事。</p><p>小明在加入我們的課程前，幾乎沒有英語基礎。經過 6 個月的翻轉課程學習，現在已經可以自信地進行英語商務談判！</p><blockquote><p>"感謝專業團隊的協助，讓我找到最適合自己的學習方法。諮商師和分析師的定期追蹤，讓我的學習動力從未中斷。"</p><footer>—— 王小明，軟體工程師</footer></blockquote><p>您也想像小明一樣突破語言障礙嗎？立即了解我們的課程！</p>',
                'status' => true,
            ],

            // 優惠通知
            [
                'title' => '新春優惠活動 - 全站課程最高享 7 折',
                'feature_img' => 'https://via.placeholder.com/800x400/F44336/FFFFFF?text=Spring+Sale',
                'notice_type_id' => $promotionType->id,
                'body' => '<p>🎉 <strong>新春優惠活動開跑啦！</strong></p><p><strong>活動時間：</strong>' . Carbon::now()->format('Y年m月d日') . ' - ' . Carbon::now()->addDays(30)->format('Y年m月d日') . '</p><p><strong>優惠內容：</strong></p><ul><li>單堂課程：<span style="color: red; font-weight: bold;">9折</span></li><li>系列課程套餐：<span style="color: red; font-weight: bold;">85折</span></li><li>翻轉課程：<span style="color: red; font-weight: bold;">7折</span></li></ul><p><strong>限時優惠，數量有限，先搶先贏！</strong></p><p><a href="/products" style="background: #F44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;">立即選購 →</a></p>',
                'status' => true,
            ],
            [
                'title' => '推薦好友送課程 - 雙重優惠活動',
                'feature_img' => 'https://via.placeholder.com/800x400/00BCD4/FFFFFF?text=Referral+Program',
                'notice_type_id' => $promotionType->id,
                'body' => '<p>推薦好友一起學習，<strong>你和朋友都能獲得優惠！</strong></p><p><strong>活動辦法：</strong></p><ol><li>分享您的專屬推薦連結給好友</li><li>好友完成註冊並購買課程</li><li>您獲得 <strong>500 元購課金</strong></li><li>好友獲得首次購課 <strong>9折優惠</strong></li></ol><p><strong>推薦越多，優惠越多！</strong></p><p>沒有推薦人數上限，快邀請您的朋友一起加入我們的學習社群吧！</p>',
                'status' => true,
            ],
            [
                'title' => '早鳥優惠 - 新學期課程預購開放',
                'feature_img' => 'https://via.placeholder.com/800x400/673AB7/FFFFFF?text=Early+Bird',
                'notice_type_id' => $promotionType->id,
                'body' => '<p>📚 新學期課程現正開放預購！</p><p><strong>早鳥優惠期限：</strong>' . Carbon::now()->format('m月d日') . ' - ' . Carbon::now()->addDays(14)->format('m月d日') . '</p><p><strong>預購優惠：</strong></p><ul><li>所有課程享 <strong>早鳥 85折</strong></li><li>加碼贈送 <strong>數位教材</strong></li><li>免費試聽一堂課</li></ul><p><strong>名額有限，把握機會！</strong></p><p>提早規劃學習計畫，讓新的一年學習更進步！</p>',
                'status' => true,
            ],
            [
                'title' => '學員專屬 - VIP 會員制度上線',
                'feature_img' => 'https://via.placeholder.com/800x400/FF5722/FFFFFF?text=VIP+Membership',
                'notice_type_id' => $promotionType->id,
                'body' => '<p>為了回饋長期支持我們的學員，<strong>VIP 會員制度</strong>正式上線！</p><p><strong>VIP 會員權益：</strong></p><ul><li>全站課程享 <strong>95折優惠</strong></li><li>優先預約熱門課程</li><li>專屬客服支援</li><li>每月免費諮商服務一次</li><li>生日當月加碼 <strong>9折優惠</strong></li></ul><p><strong>如何成為 VIP？</strong></p><p>累積消費滿 NT$10,000 即可自動升級為 VIP 會員！</p><p>已是 VIP 的學員，現在就可以開始享受專屬優惠！</p>',
                'status' => true,
            ],

            // 已過期的通知（測試用）
            [
                'title' => '聖誕節特別活動回顧',
                'feature_img' => 'https://via.placeholder.com/800x400/8BC34A/FFFFFF?text=Christmas+Event',
                'notice_type_id' => $latestType->id,
                'body' => '<p>感謝大家參與我們的聖誕節特別活動！</p><p>活動已圓滿結束，期待明年與大家再相聚！</p>',
                'status' => false, // 已關閉
            ],
        ];

        foreach ($notices as $noticeData) {
            Notice::firstOrCreate(
                ['title' => $noticeData['title']],
                $noticeData
            );
        }

        $this->command->info('✅ 通知資料建立完成！共建立 ' . count($notices) . ' 則通知');
    }
}
