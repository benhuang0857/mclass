<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\FollowerClubCourseInfo;
use App\Models\VisiblerClubCourseInfo;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use DB;

class ProductController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * 顯示所有課程實例
     */
    public function index()
    {
        $courses = Product::with([
            'clubCourseInfo',
            'followers',
            'visibleStudents',
        ])->get();

        return response()->json($courses);
    }

    /**
     * 顯示單一課程實例
     */
    public function show($id)
    {
        $course = Product::with([
            'clubCourseInfo.clubCourses',
            'clubCourseInfo.schedules',
            'followers',
            'visibleStudents',
        ])->findOrFail($id);

        return response()->json($course);
    }

    /**
     * 創建新課程實例
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code|max:255',
            'feature_img' => 'required|string|max:255',
            'regular_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'limit_enrollment' => 'boolean',
            'max_enrollment' => 'integer|min:1',
            'stock' => 'integer|min:0',
            'is_series' => 'boolean',
            'elective' => 'boolean',
            'is_visible_to_specific_students' => 'boolean',
            'status' => 'required|in:published,unpublished,sold-out',
        ]);

        DB::beginTransaction();
        try {
            $course = Product::create($validated);
            DB::commit();
            return response()->json($course->load('clubCourseInfo'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 更新課程實例
     */
    public function update(Request $request, $id)
    {
        $course = Product::findOrFail($id);
        
        // 記錄舊值用於比較
        $oldPrice = $course->discount_price ?: $course->regular_price;
        $oldStatus = $course->status;

        $rules = [
            'name' => 'sometimes|string|max:255',
            'feature_img' => 'sometimes|string|max:255',
            'regular_price' => 'sometimes|numeric|min:0',
            'discount_price' => 'sometimes|nullable|numeric|min:0',
            'limit_enrollment' => 'boolean',
            'max_enrollment' => 'integer|min:1',
            'stock' => 'integer|min:0',
            'is_series' => 'boolean',
            'elective' => 'boolean',
            'is_visible_to_specific_students' => 'boolean',
            'status' => 'sometimes|in:published,unpublished,sold-out',
        ];

        // 只有當 code 有傳且與現有不同時才驗證唯一
        if ($request->has('code')) {
            if ($request->input('code') !== $course->code) {
                $rules['code'] = 'required|string|unique:products,code,' . $id . '|max:255';
            } else {
                $rules['code'] = 'required|string|max:255';
            }
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $course->update($validated);
            
            // 檢查價格是否變更
            if (isset($validated['regular_price']) || isset($validated['discount_price'])) {
                $newPrice = $validated['discount_price'] ?? $validated['regular_price'] ?? $course->discount_price ?: $course->regular_price;
                
                if ($newPrice != $oldPrice) {
                    $this->notificationService->createCoursePriceChangeNotifications(
                        $id,
                        $oldPrice,
                        $newPrice
                    );
                }
            }
            
            // 檢查狀態是否變更
            if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
                $this->notificationService->createCourseStatusChangeNotifications(
                    $id,
                    $oldStatus,
                    $validated['status']
                );
            }
            
            DB::commit();
            return response()->json($course->load('clubCourseInfo'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 刪除課程實例
     */
    public function destroy($id)
    {
        $course = Product::findOrFail($id);

        DB::beginTransaction();
        try {
            $course->delete();
            DB::commit();
            return response()->json(['message' => 'Course instance deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 新增追蹤者
     */
    public function addFollower(Request $request, $productId)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        // 檢查是否已追蹤
        $exists = FollowerClubCourseInfo::where('member_id', $validated['member_id'])
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return response()->json(['error' => '該用戶已經追蹤，請勿重複操作'], 409);
        }

        DB::beginTransaction();
        try {
            $follower = FollowerClubCourseInfo::create([
                'member_id' => $validated['member_id'],
                'product_id' => $productId,
            ]);

            DB::commit();
            return response()->json($follower, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 刪除追蹤者
     */
    public function removeFollower(Request $request, $productId)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            FollowerClubCourseInfo::where('member_id', $validated['member_id'])
                ->where('product_id', $productId)
                ->delete();

            DB::commit();
            return response()->json(['message' => 'Follower removed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 新增可見學員（支援多個 member_id）
     */
    public function addVisibler(Request $request, $productId)
    {
        $validated = $request->validate([
            'member_id' => 'required|array',
            'member_id.*' => 'exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            $created = [];
            foreach ($validated['member_id'] as $memberId) {
                // 檢查是否已存在
                $exists = VisiblerClubCourseInfo::where('member_id', $memberId)
                    ->where('product_id', $productId)
                    ->exists();
                if ($exists) {
                    // 跳過已存在的
                    continue;
                }
                $created[] = VisiblerClubCourseInfo::create([
                    'member_id' => $memberId,
                    'product_id' => $productId,
                ]);
            }
            DB::commit();
            return response()->json($created, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 刪除可見學員（支援多個 member_id）
     */
    public function removeVisibler(Request $request, $productId)
    {
        $validated = $request->validate([
            'member_id' => 'required|array',
            'member_id.*' => 'exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            VisiblerClubCourseInfo::where('product_id', $productId)
                ->whereIn('member_id', $validated['member_id'])
                ->delete();

            DB::commit();
            return response()->json(['message' => 'Visibler(s) removed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
