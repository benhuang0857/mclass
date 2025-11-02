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
     * 更新課程實例
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