<?php

namespace App\Http\Controllers;

use App\Models\FlipCourseInfo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DB;

class FlipCourseInfoController extends Controller
{
    /**
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
            'created_by' => 'nullable|exists:users,id',
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
     * Display the specified flip course info.
     */
    public function show(int $id): JsonResponse
    {
        $flipCourse = FlipCourseInfo::with(['product', 'langTypes', 'createdBy', 'updatedBy', 'cases'])
            ->findOrFail($id);

        return response()->json($flipCourse);
    }

    /**
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
            'updated_by' => 'nullable|exists:users,id',
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
