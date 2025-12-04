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
     * @OA\Get(
     *     path="/club-course-info",
     *     summary="Get all club course information",
     *     description="Retrieve a list of all club course information with schedules and related club courses",
     *     operationId="getClubCourseInfos",
     *     tags={"Club Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Introduction to Programming"),
     *                 @OA\Property(property="code", type="string", example="CS101"),
     *                 @OA\Property(property="description", type="string", example="Learn the basics of programming"),
     *                 @OA\Property(property="details", type="string", example="This course covers variables, loops, and functions"),
     *                 @OA\Property(property="feature_img", type="string", example="https://example.com/image.jpg"),
     *                 @OA\Property(property="teaching_mode", type="string", enum={"online","offline","hybrid"}, example="online"),
     *                 @OA\Property(property="schedule_display", type="string", example="Every Monday 9:00-11:00"),
     *                 @OA\Property(property="is_periodic", type="boolean", example=true),
     *                 @OA\Property(property="total_sessions", type="integer", example=12),
     *                 @OA\Property(property="allow_replay", type="boolean", example=true),
     *                 @OA\Property(property="status", type="string", enum={"published","unpublished","completed","pending"}, example="published"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="schedules", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="clubCourses", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $courses = ClubCourseInfo::with(['schedules', 'clubCourses'])->get();
        return response()->json($courses);
    }

    /**
     * @OA\Get(
     *     path="/club-course-info/{id}",
     *     summary="Get specific club course information",
     *     description="Retrieve detailed information about a specific club course including schedules and club courses",
     *     operationId="getClubCourseInfo",
     *     tags={"Club Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club course info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Introduction to Programming"),
     *             @OA\Property(property="code", type="string", example="CS101"),
     *             @OA\Property(property="description", type="string", example="Learn the basics of programming"),
     *             @OA\Property(property="details", type="string", example="This course covers variables, loops, and functions"),
     *             @OA\Property(property="feature_img", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="teaching_mode", type="string", enum={"online","offline","hybrid"}, example="online"),
     *             @OA\Property(property="schedule_display", type="string", example="Every Monday 9:00-11:00"),
     *             @OA\Property(property="is_periodic", type="boolean", example=true),
     *             @OA\Property(property="total_sessions", type="integer", example=12),
     *             @OA\Property(property="allow_replay", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", enum={"published","unpublished","completed","pending"}, example="published"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="schedules", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-03-31"),
     *                 @OA\Property(property="day_of_week", type="string", example="monday"),
     *                 @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                 @OA\Property(property="end_time", type="string", format="time", example="11:00")
     *             )),
     *             @OA\Property(property="clubCourses", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club course info not found"
     *     )
     * )
     */
    public function show($id)
    {
        $course = ClubCourseInfo::with(['schedules', 'clubCourses'])->findOrFail($id);
        return response()->json($course);
    }

    /**
     * @OA\Post(
     *     path="/club-course-info",
     *     summary="Create new club course information",
     *     description="Create a new club course info entry with optional periodic schedules. Automatically generates club courses based on schedules or total sessions.",
     *     operationId="createClubCourseInfo",
     *     tags={"Club Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","name","code","description","details","feature_img","teaching_mode","schedule_display","total_sessions","status"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", maxLength=255, example="Introduction to Programming"),
     *             @OA\Property(property="code", type="string", example="CS101"),
     *             @OA\Property(property="description", type="string", example="Learn the basics of programming"),
     *             @OA\Property(property="details", type="string", example="This course covers variables, loops, and functions"),
     *             @OA\Property(property="feature_img", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="teaching_mode", type="string", enum={"online","offline","hybrid"}, example="online"),
     *             @OA\Property(property="schedule_display", type="string", example="Every Monday 9:00-11:00"),
     *             @OA\Property(property="is_periodic", type="boolean", example=true),
     *             @OA\Property(property="total_sessions", type="integer", minimum=1, example=12),
     *             @OA\Property(property="allow_replay", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", enum={"published","unpublished","completed","pending"}, example="published"),
     *             @OA\Property(
     *                 property="schedules",
     *                 type="array",
     *                 description="Required if is_periodic is true",
     *                 @OA\Items(
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-03-31"),
     *                     @OA\Property(property="day_of_week", type="string", enum={"monday","tuesday","wednesday","thursday","friday","saturday","sunday"}, example="monday"),
     *                     @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                     @OA\Property(property="end_time", type="string", format="time", example="11:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Club course info created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Introduction to Programming"),
     *             @OA\Property(property="code", type="string", example="CS101"),
     *             @OA\Property(property="schedules", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="clubCourses", type="array", @OA\Items(type="object"))
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
     * @OA\Put(
     *     path="/club-course-info/{id}",
     *     summary="Update club course information",
     *     description="Update an existing club course info. Regenerates club courses if schedules or total_sessions are modified.",
     *     operationId="updateClubCourseInfo",
     *     tags={"Club Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club course info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", maxLength=255, example="Advanced Programming"),
     *             @OA\Property(property="code", type="string", example="CS102"),
     *             @OA\Property(property="description", type="string", example="Advanced programming concepts"),
     *             @OA\Property(property="details", type="string", example="Covers OOP, design patterns"),
     *             @OA\Property(property="feature_img", type="string", example="https://example.com/image2.jpg"),
     *             @OA\Property(property="teaching_mode", type="string", enum={"online","offline","hybrid"}, example="hybrid"),
     *             @OA\Property(property="schedule_display", type="string", example="Every Tuesday 14:00-16:00"),
     *             @OA\Property(property="is_periodic", type="boolean", example=true),
     *             @OA\Property(property="total_sessions", type="integer", minimum=1, example=15),
     *             @OA\Property(property="allow_replay", type="boolean", example=false),
     *             @OA\Property(property="status", type="string", enum={"published","unpublished","completed","pending"}, example="published"),
     *             @OA\Property(
     *                 property="schedules",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-02-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-04-30"),
     *                     @OA\Property(property="day_of_week", type="string", example="tuesday"),
     *                     @OA\Property(property="start_time", type="string", format="time", example="14:00"),
     *                     @OA\Property(property="end_time", type="string", format="time", example="16:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club course info updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Advanced Programming"),
     *             @OA\Property(property="schedules", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="clubCourses", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club course info not found"
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
     * @OA\Delete(
     *     path="/club-course-info/{id}",
     *     summary="Delete club course information",
     *     description="Delete a club course info entry and all related schedules and club courses (cascade delete)",
     *     operationId="deleteClubCourseInfo",
     *     tags={"Club Course Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club course info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club course info deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club course info not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
