<?php

namespace App\Http\Controllers;

use App\Models\ClubCourse;
use App\Models\ClubCourseInfo;
use App\Models\ClubCourseInfoSchedule;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class ClubCourseInfoController extends Controller
{
    /**
     * 顯示所有課程資訊
     */
    public function index()
    {
        $courses = ClubCourseInfo::with(['schedules', 'clubCourses'])->get();
        return response()->json($courses);
    }

    /**
     * 顯示單一課程資訊
     */
    public function show($id)
    {
        $course = ClubCourseInfo::with(['schedules', 'clubCourses'])->findOrFail($id);
        return response()->json($course);
    }

    /**
     * 創建新課程資訊
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:club_course_infos,code',
            'description' => 'required|string',
            'details' => 'required|string',
            'feature_img' => 'required|string',
            'teaching_mode' => 'required|in:online,offline,hybrid',
            'schedule_display' => 'required|string',
            'is_periodic' => 'boolean',
            'total_sessions' => 'required|integer|min:1',
            'allow_replay' => 'boolean',
            'status' => 'required|in:published,unpublished,completed,pending',
            'schedules' => 'array|required_if:is_periodic,true',
            'schedules.*.start_date' => 'required_if:is_periodic,true|date',
            'schedules.*.end_date' => 'required_if:is_periodic,true|date|after_or_equal:schedules.*.start_date',
            'schedules.*.day_of_week' => 'required_if:is_periodic,true|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.start_time' => 'required_if:is_periodic,true|date_format:H:i',
            'schedules.*.end_time' => 'required_if:is_periodic,true|date_format:H:i|after:schedules.*.start_time',
        ]);

        DB::beginTransaction();
        try {
            // 創建課程資訊
            $courseInfo = ClubCourseInfo::create($validated);

            // 如果是週期性課程，處理排程並生成 club_courses
            if ($validated['is_periodic'] && isset($validated['schedules'])) {
                foreach ($validated['schedules'] as $scheduleData) {
                    $courseInfo->schedules()->create($scheduleData);
                }
                $this->generatePeriodicClubCourses($courseInfo);
            } else {
                // 非週期性課程，根據 total_sessions 生成 club_courses
                $this->generateClubCourses($courseInfo, $validated['total_sessions']);
            }

            DB::commit();
            return response()->json($courseInfo->load(['schedules', 'clubCourses']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 更新課程資訊
     */
    public function update(Request $request, $id)
    {
        $courseInfo = ClubCourseInfo::findOrFail($id);

        $rules = [
            'product_id' => 'required|exists:products,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'details' => 'sometimes|required|string',
            'feature_img' => 'sometimes|required|string',
            'teaching_mode' => 'sometimes|required|in:online,offline,hybrid',
            'schedule_display' => 'sometimes|required|string',
            'is_periodic' => 'boolean',
            'total_sessions' => 'sometimes|required|integer|min:1',
            'allow_replay' => 'sometimes|boolean',
            'status' => 'sometimes|required|in:published,unpublished,completed,pending',
            'schedules' => 'array|required_if:is_periodic,true',
            'schedules.*.start_date' => 'required_if:is_periodic,true|date',
            'schedules.*.end_date' => 'required_if:is_periodic,true|date|after_or_equal:schedules.*.start_date',
            'schedules.*.day_of_week' => 'required_if:is_periodic,true|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.start_time' => 'required_if:is_periodic,true|date_format:H:i',
            'schedules.*.end_time' => 'required_if:is_periodic,true|date_format:H:i|after:schedules.*.start_time',
        ];

        // 只有當 code 有變更時才加上唯一性驗證
        if ($request->has('code') && $request->input('code') !== $courseInfo->code) {
            $rules['code'] = 'required|string|unique:club_course_infos,code,' . $id;
        } else {
            $rules['code'] = 'sometimes|required|string';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // 更新課程資訊
            $courseInfo->update($validated);

            // 如果是週期性課程，更新排程並重新生成 club_courses
            if ($validated['is_periodic'] ?? $courseInfo->is_periodic) {
                // 清空原有排程
                $courseInfo->schedules()->delete();
                if (isset($validated['schedules'])) {
                    foreach ($validated['schedules'] as $scheduleData) {
                        $courseInfo->schedules()->create($scheduleData);
                    }
                }
                // 清空原有 club_courses 並重新生成
                $courseInfo->clubCourses()->delete();
                $this->generatePeriodicClubCourses($courseInfo);
            } elseif (isset($validated['total_sessions'])) {
                // 非週期性課程，根據 total_sessions 更新 club_courses
                $courseInfo->clubCourses()->delete();
                $this->generateClubCourses($courseInfo, $validated['total_sessions']);
            }

            DB::commit();
            return response()->json($courseInfo->load(['schedules', 'clubCourses']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 刪除課程資訊
     */
    public function destroy($id)
    {
        $courseInfo = ClubCourseInfo::findOrFail($id);
        DB::beginTransaction();
        try {
            // 刪除相關數據（由於遷移中設置了 onDelete('cascade')，相關表會自動清理）
            $courseInfo->delete();
            DB::commit();
            return response()->json(['message' => 'Course deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 根據 total_sessions 生成 club_courses
     */
    private function generateClubCourses(ClubCourseInfo $courseInfo, int $totalSessions)
    {
        // 假設課程從當前日期開始，每週一次（可根據需求調整）
        $startDate = Carbon::today();
        for ($i = 0; $i < $totalSessions; $i++) {
            $courseDate = $startDate->copy()->addWeeks($i);
            ClubCourse::create([
                'course_id' => $courseInfo->id,
                'start_time' => $courseDate->setTime(9, 0), // 假設上午9點開始
                'end_time' => $courseDate->setTime(11, 0), // 假設上午11點結束
                'trial' => $i === 0, // 第一堂課為試聽
                'sort' => $i + 1,
            ]);
        }
    }

    /**
     * 根據 club_course_info_schedule 生成週期性 club_courses
     */
    private function generatePeriodicClubCourses(ClubCourseInfo $courseInfo)
    {
        $schedules = $courseInfo->schedules;
        foreach ($schedules as $schedule) {
            $startDate = Carbon::parse($schedule->start_date);
            $endDate = Carbon::parse($schedule->end_date);
            $dayOfWeek = $schedule->day_of_week;
            $startTime = Carbon::parse($schedule->start_time);
            $endTime = Carbon::parse($schedule->end_time);

            $currentDate = $startDate->copy();
            $sort = 1;

            while ($currentDate->lte($endDate)) {
                if (strtolower($currentDate->englishDayOfWeek) === strtolower($dayOfWeek)) {
                    ClubCourse::create([
                        'course_id' => $courseInfo->id,
                        'start_time' => $currentDate->copy()->setTime($startTime->hour, $startTime->minute),
                        'end_time' => $currentDate->copy()->setTime($endTime->hour, $endTime->minute),
                        'trial' => $sort === 1,
                        'sort' => $sort++,
                    ]);
                }
                $currentDate->addDay();
            }
        }
    }
}
