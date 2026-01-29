<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 取得角色（用於分配權限）
        $roles = Role::all()->keyBy('slug');

        // 定義角色群組
        $studentRoles = [$roles->get('student')?->id];
        $teacherRoles = [$roles->get('teacher')?->id, $roles->get('assistant')?->id];
        $plannerRoles = [$roles->get('planner')?->id];
        $counselorRoles = [$roles->get('counselor')?->id];
        $analystRoles = [$roles->get('analyst')?->id];
        $salesRoles = [$roles->get('sales')?->id];

        // 管理人員角色（需要訪問系統管理功能）
        $adminRoles = array_filter([
            $roles->get('planner')?->id,
            $roles->get('sales')?->id,
        ]);

        // 所有角色（用於公共菜單）
        $allRoles = $roles->pluck('id')->filter()->toArray();

        // ============ 一級菜單 ============

        // 1. 首頁
        $dashboard = $this->createMenu([
            'name' => '首頁',
            'icon' => 'dashboard',
            'url' => '/dashboard',
            'display_order' => 1,
            'visible_to_all' => true,
        ], $allRoles);

        // 2. 課程管理
        $courseMenu = $this->createMenu([
            'name' => '課程管理',
            'icon' => 'school',
            'url' => null,
            'display_order' => 2,
            'visible_to_all' => false,
        ], array_merge($teacherRoles, $adminRoles));

        // 3. 會員管理
        $memberMenu = $this->createMenu([
            'name' => '會員管理',
            'icon' => 'people',
            'url' => '/members',
            'display_order' => 3,
            'visible_to_all' => false,
        ], $adminRoles);

        // 4. 產品管理
        $productMenu = $this->createMenu([
            'name' => '產品管理',
            'icon' => 'inventory',
            'url' => '/products',
            'display_order' => 4,
            'visible_to_all' => false,
        ], $adminRoles);

        // 5. 訂單管理
        $orderMenu = $this->createMenu([
            'name' => '訂單管理',
            'icon' => 'receipt',
            'url' => '/orders',
            'display_order' => 5,
            'visible_to_all' => false,
        ], $adminRoles);

        // 6. 我的學習（學生專用）
        $myLearning = $this->createMenu([
            'name' => '我的學習',
            'icon' => 'book',
            'url' => null,
            'display_order' => 6,
            'visible_to_all' => false,
        ], $studentRoles);

        // 7. 翻轉課程工作台（規劃師、諮商師、分析師）
        $flipWorkspace = $this->createMenu([
            'name' => '翻轉課程工作台',
            'icon' => 'work',
            'url' => null,
            'display_order' => 7,
            'visible_to_all' => false,
        ], array_merge($plannerRoles, $counselorRoles, $analystRoles));

        // 8. 諮商管理
        $counselingMenu = $this->createMenu([
            'name' => '諮商管理',
            'icon' => 'psychology',
            'url' => null,
            'display_order' => 8,
            'visible_to_all' => false,
        ], $counselorRoles);

        // 9. 系統設定
        $systemMenu = $this->createMenu([
            'name' => '系統設定',
            'icon' => 'settings',
            'url' => null,
            'display_order' => 9,
            'visible_to_all' => false,
        ], $adminRoles);

        // ============ 二級菜單 ============

        // 2.1 課程管理 > 子菜單
        $this->createMenu([
            'name' => '俱樂部課程',
            'icon' => 'groups',
            'url' => '/courses/club',
            'parent_id' => $courseMenu->id,
            'display_order' => 1,
            'visible_to_all' => false,
        ], array_merge($teacherRoles, $adminRoles));

        $this->createMenu([
            'name' => '翻轉課程',
            'icon' => 'flip',
            'url' => '/courses/flip',
            'parent_id' => $courseMenu->id,
            'display_order' => 2,
            'visible_to_all' => false,
        ], $adminRoles);

        $this->createMenu([
            'name' => '諮商服務',
            'icon' => 'support',
            'url' => '/courses/counseling',
            'parent_id' => $courseMenu->id,
            'display_order' => 3,
            'visible_to_all' => false,
        ], array_merge($counselorRoles, $adminRoles));

        $this->createMenu([
            'name' => 'Zoom 憑證管理',
            'icon' => 'videocam',
            'url' => '/zoom-credentials',
            'parent_id' => $courseMenu->id,
            'display_order' => 4,
            'visible_to_all' => false,
        ], $adminRoles);

        // 6.1 我的學習 > 子菜單
        $this->createMenu([
            'name' => '我的課程',
            'icon' => 'class',
            'url' => '/my/courses',
            'parent_id' => $myLearning->id,
            'display_order' => 1,
            'visible_to_all' => false,
        ], $studentRoles);

        $this->createMenu([
            'name' => '我的諮商',
            'icon' => 'chat',
            'url' => '/my/counseling',
            'parent_id' => $myLearning->id,
            'display_order' => 2,
            'visible_to_all' => false,
        ], $studentRoles);

        $this->createMenu([
            'name' => '我的翻轉課程',
            'icon' => 'auto_stories',
            'url' => '/my/flip-courses',
            'parent_id' => $myLearning->id,
            'display_order' => 3,
            'visible_to_all' => false,
        ], $studentRoles);

        $this->createMenu([
            'name' => '我的訂單',
            'icon' => 'shopping_cart',
            'url' => '/my/orders',
            'parent_id' => $myLearning->id,
            'display_order' => 4,
            'visible_to_all' => false,
        ], $studentRoles);

        // 7.1 翻轉課程工作台 > 子菜單
        $this->createMenu([
            'name' => '案例列表',
            'icon' => 'list',
            'url' => '/flip-workspace/cases',
            'parent_id' => $flipWorkspace->id,
            'display_order' => 1,
            'visible_to_all' => false,
        ], array_merge($plannerRoles, $counselorRoles, $analystRoles));

        $this->createMenu([
            'name' => '我的任務',
            'icon' => 'task',
            'url' => '/flip-workspace/tasks',
            'parent_id' => $flipWorkspace->id,
            'display_order' => 2,
            'visible_to_all' => false,
        ], array_merge($plannerRoles, $counselorRoles, $analystRoles));

        // 8.1 諮商管理 > 子菜單
        $this->createMenu([
            'name' => '諮商預約',
            'icon' => 'event',
            'url' => '/counseling/appointments',
            'parent_id' => $counselingMenu->id,
            'display_order' => 1,
            'visible_to_all' => false,
        ], $counselorRoles);

        $this->createMenu([
            'name' => '諮商記錄',
            'icon' => 'history',
            'url' => '/counseling/records',
            'parent_id' => $counselingMenu->id,
            'display_order' => 2,
            'visible_to_all' => false,
        ], $counselorRoles);

        // 9.1 系統設定 > 子菜單
        $this->createMenu([
            'name' => '角色管理',
            'icon' => 'badge',
            'url' => '/settings/roles',
            'parent_id' => $systemMenu->id,
            'display_order' => 1,
            'visible_to_all' => false,
        ], $adminRoles);

        $this->createMenu([
            'name' => '菜單管理',
            'icon' => 'menu',
            'url' => '/settings/menus',
            'parent_id' => $systemMenu->id,
            'display_order' => 2,
            'visible_to_all' => false,
        ], $adminRoles);

        $this->createMenu([
            'name' => '類型管理',
            'icon' => 'category',
            'url' => '/settings/types',
            'parent_id' => $systemMenu->id,
            'display_order' => 3,
            'visible_to_all' => false,
        ], $adminRoles);

        $this->createMenu([
            'name' => '輪播管理',
            'icon' => 'slideshow',
            'url' => '/settings/slideshows',
            'parent_id' => $systemMenu->id,
            'display_order' => 4,
            'visible_to_all' => false,
        ], $adminRoles);

        $this->createMenu([
            'name' => '通知管理',
            'icon' => 'notifications',
            'url' => '/settings/notices',
            'parent_id' => $systemMenu->id,
            'display_order' => 5,
            'visible_to_all' => false,
        ], $adminRoles);

        $this->command->info('✅ 菜單資料建立完成！');
    }

    /**
     * 建立菜單並分配角色權限
     *
     * @param array $menuData
     * @param array $roleIds
     * @return Menu
     */
    private function createMenu(array $menuData, array $roleIds = []): Menu
    {
        // 設定預設值
        $menuData = array_merge([
            'target' => '_self',
            'status' => true,
            'visible_to_all' => false,
            'note' => null,
        ], $menuData);

        // 建立菜單（使用 firstOrCreate 避免重複）
        $menu = Menu::firstOrCreate(
            [
                'name' => $menuData['name'],
                'parent_id' => $menuData['parent_id'] ?? null,
            ],
            $menuData
        );

        // 分配角色權限（過濾掉 null 值）
        $validRoleIds = array_filter($roleIds);
        if (!empty($validRoleIds)) {
            $menu->roles()->syncWithoutDetaching($validRoleIds);
        }

        return $menu;
    }
}
