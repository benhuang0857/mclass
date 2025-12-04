<?php

namespace App\Http\Controllers;

use App\Models\ClubCourse;
use App\Models\ClubCourseInfo;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ZoomController extends Controller
{
    protected $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * @OA\Get(
     *     path="/zoom/check",
     *     tags={"Zoom"},
     *     summary="Check Zoom API connection",
     *     description="Check the status of Zoom API connection",
     *     @OA\Response(
     *         response=200,
     *         description="Connection check result"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Connection check failed"
     *     )
     * )
     */
    public function checkConnection(): JsonResponse
    {
        try {
            $result = $this->zoomService->checkConnection();
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '連接檢查失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/zoom/courses/{course}/meeting",
     *     tags={"Zoom"},
     *     summary="Create Zoom meeting for a course",
     *     description="Create a Zoom meeting for a specific course",
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="topic", type="string", maxLength=255, description="Meeting topic"),
     *             @OA\Property(property="agenda", type="string", maxLength=1000, description="Meeting agenda"),
     *             @OA\Property(property="password", type="string", minLength=4, maxLength=10, description="Meeting password"),
     *             @OA\Property(property="settings", type="object", description="Meeting settings",
     *                 @OA\Property(property="host_video", type="boolean"),
     *                 @OA\Property(property="participant_video", type="boolean"),
     *                 @OA\Property(property="join_before_host", type="boolean"),
     *                 @OA\Property(property="mute_upon_entry", type="boolean"),
     *                 @OA\Property(property="waiting_room", type="boolean"),
     *                 @OA\Property(property="auto_recording", type="string", enum={"local", "cloud", "none"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Meeting created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create meeting"
     *     )
     * )
     */
    public function createMeetingForCourse(Request $request, ClubCourse $course): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'nullable|string|max:255',
            'agenda' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:4|max:10',
            'settings' => 'nullable|array',
            'settings.host_video' => 'nullable|boolean',
            'settings.participant_video' => 'nullable|boolean',
            'settings.join_before_host' => 'nullable|boolean',
            'settings.mute_upon_entry' => 'nullable|boolean',
            'settings.waiting_room' => 'nullable|boolean',
            'settings.auto_recording' => 'nullable|string|in:local,cloud,none',
        ]);

        try {
            $result = $this->zoomService->createMeetingForCourse($course, $validated);
            
            if ($result['success']) {
                return response()->json($result, 201);
            }
            
            return response()->json($result, 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '創建會議失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/zoom/courses/{course}/meeting",
     *     tags={"Zoom"},
     *     summary="Get course Zoom meeting info",
     *     description="Retrieve Zoom meeting information for a course",
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course has no Zoom meeting"
     *     )
     * )
     */
    public function getCourseZoomInfo(ClubCourse $course): JsonResponse
    {
        try {
            $zoomDetail = $course->zoomMeetDetail;
            
            if (!$zoomDetail) {
                return response()->json([
                    'success' => false,
                    'message' => '此課程尚未創建 Zoom 會議'
                ], 404);
            }

            // 如果需要從 Zoom API 獲取最新資訊
            $zoomApiResult = $this->zoomService->getMeetingInfo($zoomDetail->zoom_meeting_id);
            
            $response = [
                'success' => true,
                'data' => [
                    'local_info' => [
                        'course_id' => $course->id,
                        'course_name' => $course->courseInfo->name ?? '未知課程',
                        'start_time' => $course->start_time,
                        'end_time' => $course->end_time,
                        'location' => $course->location,
                        'trial' => $course->trial,
                        'zoom_detail' => $zoomDetail,
                    ]
                ]
            ];

            if ($zoomApiResult['success']) {
                $response['data']['zoom_api_info'] = $zoomApiResult['data'];
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取會議資訊失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/zoom/courses/{course}/meeting",
     *     tags={"Zoom"},
     *     summary="Delete Zoom meeting",
     *     description="Delete the Zoom meeting for a course",
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Meeting deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete meeting"
     *     )
     * )
     */
    public function deleteMeeting(ClubCourse $course): JsonResponse
    {
        try {
            $result = $this->zoomService->deleteMeeting($course);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '刪除會議失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/zoom/courses/{course}/join",
     *     tags={"Zoom"},
     *     summary="Get student join URL",
     *     description="Get the Zoom meeting join URL for students",
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course has no Zoom meeting"
     *     )
     * )
     */
    public function getCourseJoinUrl(ClubCourse $course): JsonResponse
    {
        try {
            $zoomDetail = $course->zoomMeetDetail;
            
            if (!$zoomDetail) {
                return response()->json([
                    'success' => false,
                    'message' => '此課程尚未創建 Zoom 會議'
                ], 404);
            }

            // 檢查課程是否即將開始（提前30分鐘開放）
            $canJoin = $zoomDetail->canJoinNow();
            $timeRange = $zoomDetail->getJoinTimeRange();

            return response()->json([
                'success' => true,
                'data' => [
                    'course_id' => $course->id,
                    'course_name' => $course->courseInfo->name ?? '未知課程',
                    'join_url' => $zoomDetail->join_url,
                    'meeting_id' => $zoomDetail->zoom_meeting_id,
                    'password' => $zoomDetail->password,
                    'start_time' => $course->start_time,
                    'end_time' => $course->end_time,
                    'trial' => $course->trial,
                    'can_join' => $canJoin,
                    'time_range' => $timeRange,
                    'location' => $course->location,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取會議連結失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/zoom/courses/{course}/start",
     *     tags={"Zoom"},
     *     summary="Get host start URL",
     *     description="Get the Zoom meeting start URL for the host/teacher",
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course has no Zoom meeting"
     *     )
     * )
     */
    public function getHostStartUrl(ClubCourse $course): JsonResponse
    {
        try {
            $zoomDetail = $course->zoomMeetDetail;
            
            if (!$zoomDetail) {
                return response()->json([
                    'success' => false,
                    'message' => '此課程尚未創建 Zoom 會議'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'course_id' => $course->id,
                    'course_name' => $course->courseInfo->name ?? '未知課程',
                    'start_url' => $zoomDetail->start_url,
                    'meeting_id' => $zoomDetail->zoom_meeting_id,
                    'password' => $zoomDetail->password,
                    'topic' => $zoomDetail->topic,
                    'start_time' => $course->start_time,
                    'end_time' => $course->end_time,
                    'duration' => $zoomDetail->duration,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取主持人連結失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}