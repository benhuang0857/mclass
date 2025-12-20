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
     * @OA\Get(
     *     path="/products",
     *     summary="Get all products (courses)",
     *     description="Retrieve a list of all course products with their information, followers, and visible students",
     *     operationId="getProductsList",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="club_course_info_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=99.99),
     *                 @OA\Property(property="inventory", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Get a specific product",
     *     description="Retrieve detailed information about a specific course product",
     *     operationId="getProductById",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="club_course_info_id", type="integer", example=1),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="inventory", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
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
     * @OA\Post(
     *     path="/products",
     *     summary="Create a new product",
     *     description="Create a new course product with pricing, enrollment limits, and visibility settings",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code","feature_img","regular_price","status"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Web Development Bootcamp"),
     *             @OA\Property(property="code", type="string", maxLength=255, example="WDB2025"),
     *             @OA\Property(property="feature_img", type="string", maxLength=255, example="https://example.com/course.jpg"),
     *             @OA\Property(property="regular_price", type="number", format="float", minimum=0, example=199.99),
     *             @OA\Property(property="discount_price", type="number", format="float", minimum=0, example=149.99),
     *             @OA\Property(property="limit_enrollment", type="boolean", example=true),
     *             @OA\Property(property="max_enrollment", type="integer", minimum=1, example=30),
     *             @OA\Property(property="stock", type="integer", minimum=0, example=25),
     *             @OA\Property(property="is_series", type="boolean", example=false),
     *             @OA\Property(property="elective", type="boolean", example=true),
     *             @OA\Property(property="is_visible_to_specific_students", type="boolean", example=false),
     *             @OA\Property(property="status", type="string", enum={"published","unpublished","sold-out"}, example="published")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Web Development Bootcamp"),
     *             @OA\Property(property="code", type="string", example="WDB2025"),
     *             @OA\Property(property="regular_price", type="number", example=199.99)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
     * @OA\Put(
     *     path="/products/{id}",
     *     summary="Update product information",
     *     description="Update an existing product. Sends notifications if price or status changes.",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Advanced Web Development"),
     *             @OA\Property(property="code", type="string", maxLength=255, example="AWD2025"),
     *             @OA\Property(property="feature_img", type="string", maxLength=255, example="https://example.com/new-image.jpg"),
     *             @OA\Property(property="regular_price", type="number", format="float", minimum=0, example=249.99),
     *             @OA\Property(property="discount_price", type="number", format="float", minimum=0, example=199.99),
     *             @OA\Property(property="limit_enrollment", type="boolean", example=true),
     *             @OA\Property(property="max_enrollment", type="integer", minimum=1, example=25),
     *             @OA\Property(property="stock", type="integer", minimum=0, example=20),
     *             @OA\Property(property="is_series", type="boolean", example=true),
     *             @OA\Property(property="elective", type="boolean", example=false),
     *             @OA\Property(property="is_visible_to_specific_students", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", enum={"published","unpublished","sold-out"}, example="published")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Advanced Web Development"),
     *             @OA\Property(property="regular_price", type="number", example=249.99)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/products/{id}",
     *     summary="Delete product",
     *     description="Delete a course product",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course instance deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
     * @OA\Post(
     *     path="/products/{id}/follower",
     *     summary="Add follower to product",
     *     description="Add a member as a follower of a course product. Prevents duplicate followers.",
     *     operationId="addProductFollower",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(property="member_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Follower added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="member_id", type="integer", example=5),
     *             @OA\Property(property="product_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Member already following",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="該用戶已經追蹤,請勿重複操作")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/products/{id}/follower",
     *     summary="Remove follower from product",
     *     description="Remove a member from following a course product",
     *     operationId="removeProductFollower",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(property="member_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Follower removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Follower removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
     * @OA\Post(
     *     path="/products/{id}/visibler",
     *     summary="Add visible students to product",
     *     description="Add one or more members to the visible students list for a product. Supports batch adding and skips duplicates.",
     *     operationId="addProductVisibler",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(
     *                 property="member_id",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={5, 7, 10}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Visible students added successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=5),
     *                 @OA\Property(property="product_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/products/{id}/visibler",
     *     summary="Remove visible students from product",
     *     description="Remove one or more members from the visible students list for a product. Supports batch removal.",
     *     operationId="removeProductVisibler",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(
     *                 property="member_id",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={5, 7, 10}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Visible students removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Visibler(s) removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
