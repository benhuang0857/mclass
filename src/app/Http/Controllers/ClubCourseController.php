<?php

namespace App\Http\Controllers;

use App\Models\ClubCourse;
use App\Models\ClubCourseInfo;
use App\Models\ClubCourseInfoSchedule;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class ClubCourseController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * @OA\Get(
     *     path="/api/club-course",
     *     summary="Get all club courses",
     *     description="Retrieve a list of all club course instances with their course info",
     *     operationId="getClubCoursesList",
     *     tags={"Club Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-10 09:00:00"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-10 11:00:00"),
     *                 @OA\Property(property="link", type="string", nullable=true, example="https://zoom.us/j/123456"),
     *                 @OA\Property(property="location", type="string", nullable=true, example="Room 101"),
     *                 @OA\Property(property="trial", type="boolean", example=false),
     *                 @OA\Property(property="sort", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $courses = ClubCourse::with(['courseInfo'])->get();
        return response()->json($courses);
    }

    /**
     * @OA\Get(
     *     path="/api/club-course/{id}",
     *     summary="Get a specific club course",
     *     description="Retrieve detailed information about a specific club course instance",
     *     operationId="getClubCourseById",
     *     tags={"Club Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-10 09:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-10 11:00:00"),
     *             @OA\Property(property="link", type="string", nullable=true, example="https://zoom.us/j/123456"),
     *             @OA\Property(property="location", type="string", nullable=true, example="Room 101"),
     *             @OA\Property(property="trial", type="boolean", example=false),
     *             @OA\Property(property="sort", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club course not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        $course = ClubCourse::with(['courseInfo'])->findOrFail($id);
        return response()->json($course);
    }

    /**
     * @OA\Post(
     *     path="/api/club-course",
     *     summary="Create a new club course instance",
     *     description="Create a new club course session with schedule details. Automatically sends notifications to course followers.",
     *     operationId="createClubCourse",
     *     tags={"Club Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Club course data",
     *         @OA\JsonContent(
     *             required={"course_id", "start_time", "end_time", "sort"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-10 09:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-10 11:00:00"),
     *             @OA\Property(property="link", type="string", format="uri", nullable=true, example="https://zoom.us/j/123456"),
     *             @OA\Property(property="location", type="string", nullable=true, example="Room 101"),
     *             @OA\Property(property="trial", type="boolean", example=false),
     *             @OA\Property(property="sort", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Club course created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-10 09:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-10 11:00:00"),
     *             @OA\Property(property="sort", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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

            // 發送新班次通知給追蹤該課程的用戶
            $this->notificationService->createCourseNewClassNotifications($course->id);

            // 創建報名截止提醒（課程開始前24小時）
            $this->notificationService->createCourseRegistrationDeadlineNotifications($course->id, 24);

            DB::commit();
            return response()->json($course->load('courseInfo'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/club-course/{id}",
     *     summary="Update a club course instance",
     *     description="Update an existing club course session. Sends notifications if time or location changes.",
     *     operationId="updateClubCourse",
     *     tags={"Club Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Club course data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-10 10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-10 12:00:00"),
     *             @OA\Property(property="link", type="string", format="uri", nullable=true, example="https://zoom.us/j/654321"),
     *             @OA\Property(property="location", type="string", nullable=true, example="Room 202"),
     *             @OA\Property(property="trial", type="boolean", example=false),
     *             @OA\Property(property="sort", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club course updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-10 10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-10 12:00:00"),
     *             @OA\Property(property="location", type="string", example="Room 202")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club course not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $course = ClubCourse::findOrFail($id);
        $oldStartTime = $course->start_time;
        $oldLocation = $course->location;

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

            // 檢查時間是否變更
            if (isset($validated['start_time']) && $validated['start_time'] !== $oldStartTime) {
                $this->notificationService->createCourseChangeNotifications(
                    $id,
                    'time',
                    $oldStartTime,
                    $validated['start_time']
                );
            }

            // 檢查地點是否變更
            if (isset($validated['location']) && $validated['location'] !== $oldLocation) {
                $this->notificationService->createCourseChangeNotifications(
                    $id,
                    'location',
                    $oldLocation,
                    $validated['location']
                );
            }

            DB::commit();
            return response()->json($course->load('courseInfo'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/club-course/{id}",
     *     summary="Delete a club course instance",
     *     description="Delete a specific club course session from the system",
     *     operationId="deleteClubCourse",
     *     tags={"Club Courses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club course deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Course instance deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club course not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
