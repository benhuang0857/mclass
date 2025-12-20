<?php

namespace App\Http\Controllers;

use App\Models\FlipCourseInfo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DB;

class FlipCourseInfoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/flip-course-infos",
     *     summary="Get flip course infos list",
     *     description="Retrieve list of flip course information templates with filtering and pagination",
     *     operationId="getFlipCourseInfos",
     *     tags={"Flip Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"draft", "published", "archived"}, example="published")
     *     ),
     *     @OA\Parameter(
     *         name="teaching_mode",
     *         in="query",
     *         description="Filter by teaching mode",
     *         @OA\Schema(type="string", enum={"online", "offline", "hybrid"}, example="online")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or code",
     *         @OA\Schema(type="string", example="Python")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Advanced Python Flip Course"),
     *                     @OA\Property(property="code", type="string", example="FLP-PY-001"),
     *                     @OA\Property(property="status", type="string", example="published"),
     *                     @OA\Property(property="teaching_mode", type="string", example="online")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
     * Display a listing of flip course infos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FlipCourseInfo::with(['product', 'langTypes', 'createdBy', 'updatedBy']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by teaching mode
        if ($request->has('teaching_mode')) {
            $query->where('teaching_mode', $request->teaching_mode);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $flipCourses = $query->paginate($request->get('per_page', 15));

        return response()->json($flipCourses);
    }

    /**
     * @OA\Post(
     *     path="/flip-course-infos",
     *     summary="Create flip course info",
     *     description="Create a new flip course information template",
     *     operationId="createFlipCourseInfo",
     *     tags={"Flip Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Flip course info data",
     *         @OA\JsonContent(
     *             required={"product_id", "name", "code", "description", "details", "feature_img", "teaching_mode"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Advanced Python Flip Course"),
     *             @OA\Property(property="code", type="string", example="FLP-PY-001"),
     *             @OA\Property(property="description", type="string", example="Comprehensive Python programming flip course"),
     *             @OA\Property(property="details", type="string", example="Detailed course curriculum and objectives"),
     *             @OA\Property(property="feature_img", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="teaching_mode", type="string", enum={"online", "offline", "hybrid"}, example="online"),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="draft"),
     *             @OA\Property(property="created_by", type="integer", example=2),
     *             @OA\Property(property="lang_type_ids", type="array", @OA\Items(type="integer"), example={1, 2})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Flip course info created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Flip course info created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     *
     * Store a newly created flip course info.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:flip_course_infos,code',
            'description' => 'required|string',
            'details' => 'required|string',
            'feature_img' => 'required|string',
            'teaching_mode' => 'required|in:online,offline,hybrid',
            'status' => 'nullable|in:draft,published,archived',
            'created_by' => 'nullable|exists:members,id',
            'lang_type_ids' => 'nullable|array',
            'lang_type_ids.*' => 'exists:lang_types,id',
        ]);

        DB::beginTransaction();
        try {
            $flipCourse = FlipCourseInfo::create($validated);

            // Attach language types
            if (!empty($validated['lang_type_ids'])) {
                $flipCourse->langTypes()->attach($validated['lang_type_ids']);
            }

            DB::commit();
            return response()->json([
                'message' => 'Flip course info created successfully',
                'data' => $flipCourse->load(['product', 'langTypes', 'createdBy'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/flip-course-infos/{id}",
     *     summary="Get flip course info details",
     *     description="Retrieve detailed information about a specific flip course template",
     *     operationId="getFlipCourseInfo",
     *     tags={"Flip Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Advanced Python Flip Course"),
     *             @OA\Property(property="code", type="string", example="FLP-PY-001"),
     *             @OA\Property(property="description", type="string", example="Comprehensive Python programming flip course"),
     *             @OA\Property(property="teaching_mode", type="string", example="online"),
     *             @OA\Property(property="status", type="string", example="published")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Flip course info not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
     * Display the specified flip course info.
     */
    public function show(int $id): JsonResponse
    {
        $flipCourse = FlipCourseInfo::with(['product', 'langTypes', 'createdBy', 'updatedBy', 'cases'])
            ->findOrFail($id);

        return response()->json($flipCourse);
    }

    /**
     * @OA\Put(
     *     path="/flip-course-infos/{id}",
     *     summary="Update flip course info",
     *     description="Update an existing flip course information template",
     *     operationId="updateFlipCourseInfo",
     *     tags={"Flip Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Flip course info update data",
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Advanced Python Flip Course"),
     *             @OA\Property(property="code", type="string", example="FLP-PY-001"),
     *             @OA\Property(property="description", type="string", example="Updated course description"),
     *             @OA\Property(property="details", type="string", example="Updated detailed curriculum"),
     *             @OA\Property(property="feature_img", type="string", example="https://example.com/new-image.jpg"),
     *             @OA\Property(property="teaching_mode", type="string", enum={"online", "offline", "hybrid"}, example="hybrid"),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published"),
     *             @OA\Property(property="updated_by", type="integer", example=2),
     *             @OA\Property(property="lang_type_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Flip course info updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Flip course info updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Flip course info not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     *
     * Update the specified flip course info.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $flipCourse = FlipCourseInfo::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:flip_course_infos,code,' . $id,
            'description' => 'sometimes|string',
            'details' => 'sometimes|string',
            'feature_img' => 'sometimes|string',
            'teaching_mode' => 'sometimes|in:online,offline,hybrid',
            'status' => 'sometimes|in:draft,published,archived',
            'updated_by' => 'nullable|exists:members,id',
            'lang_type_ids' => 'nullable|array',
            'lang_type_ids.*' => 'exists:lang_types,id',
        ]);

        DB::beginTransaction();
        try {
            $flipCourse->update($validated);

            // Sync language types if provided
            if (isset($validated['lang_type_ids'])) {
                $flipCourse->langTypes()->sync($validated['lang_type_ids']);
            }

            DB::commit();
            return response()->json([
                'message' => 'Flip course info updated successfully',
                'data' => $flipCourse->load(['product', 'langTypes', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/flip-course-infos/{id}",
     *     summary="Delete flip course info",
     *     description="Delete a flip course information template",
     *     operationId="deleteFlipCourseInfo",
     *     tags={"Flip Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Flip course info deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Flip course info deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Flip course info not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     *
     * Remove the specified flip course info.
     */
    public function destroy(int $id): JsonResponse
    {
        $flipCourse = FlipCourseInfo::findOrFail($id);

        DB::beginTransaction();
        try {
            $flipCourse->delete();
            DB::commit();
            return response()->json([
                'message' => 'Flip course info deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/flip-course-infos/{id}/statistics",
     *     summary="Get flip course statistics",
     *     description="Retrieve statistics about flip course cases for a specific flip course template",
     *     operationId="getFlipCourseStatistics",
     *     tags={"Flip Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_cases", type="integer", example=45),
     *             @OA\Property(property="active_cases", type="integer", example=12),
     *             @OA\Property(property="completed_cases", type="integer", example=30),
     *             @OA\Property(property="cancelled_cases", type="integer", example=3),
     *             @OA\Property(property="by_stage", type="object",
     *                 @OA\Property(property="planning", type="integer", example=5),
     *                 @OA\Property(property="counseling", type="integer", example=4),
     *                 @OA\Property(property="cycling", type="integer", example=3),
     *                 @OA\Property(property="completed", type="integer", example=30)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Flip course info not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
     * Get statistics about flip course cases.
     */
    public function getStatistics(int $id): JsonResponse
    {
        $flipCourse = FlipCourseInfo::findOrFail($id);

        $stats = [
            'total_cases' => $flipCourse->cases()->count(),
            'active_cases' => $flipCourse->cases()->whereNotIn('workflow_stage', ['completed', 'cancelled'])->count(),
            'completed_cases' => $flipCourse->cases()->where('workflow_stage', 'completed')->count(),
            'cancelled_cases' => $flipCourse->cases()->where('workflow_stage', 'cancelled')->count(),
            'by_stage' => $flipCourse->cases()
                ->selectRaw('workflow_stage, count(*) as count')
                ->groupBy('workflow_stage')
                ->pluck('count', 'workflow_stage'),
        ];

        return response()->json($stats);
    }
}
