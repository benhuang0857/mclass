<?php

namespace App\Http\Controllers;

use App\Models\ClubCourse;
use App\Models\ClubCourseInfo;
use App\Models\ClubCourseInfoSchedule;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class ClubCourseController extends Controller
{
    /**
     * 顯示所有課程實例
     */
    public function index()
    {
        $courses = ClubCourse::with(['courseInfo'])->get();
        return response()->json($courses);
    }

    /**
     * 顯示單一課程實例
     */
    public function show($id)
    {
        $course = ClubCourse::with(['courseInfo'])->findOrFail($id);
        return response()->json($course);
    }

    /**
     * 創建新課程實例
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:club_course_infos,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'link' => 'nullable|url',
            'location' => 'nullable|string|max:255',
            'trial' => 'boolean',
            'sort' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $course = ClubCourse::create($validated);
            DB::commit();
            return response()->json($course->load('courseInfo'), 201);
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
        $course = ClubCourse::findOrFail($id);

        $validated = $request->validate([
            'course_id' => 'sometimes|required|exists:club_course_infos,id',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'link' => 'nullable|url',
            'location' => 'nullable|string|max:255',
            'trial' => 'sometimes|boolean',
            'sort' => 'sometimes|required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $course->update($validated);
            DB::commit();
            return response()->json($course->load('courseInfo'));
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
        $course = ClubCourse::findOrFail($id);

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
}