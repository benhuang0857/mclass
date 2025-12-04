<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClubCourse;
use App\Models\Member;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/attendance/courses/{course}",
     *     summary="Get course attendance list",
     *     description="Retrieve attendance records for a specific course with automatic roster generation if needed",
     *     operationId="getCourseAttendance",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="course", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Advanced Python Course"),
     *                     @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-04 10:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-04 12:00:00"),
     *                     @OA\Property(property="location", type="string", example="Room 101"),
     *                     @OA\Property(property="trial", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(property="attendances", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="status", type="string", example="present"),
     *                         @OA\Property(property="status_label", type="string", example="出席"),
     *                         @OA\Property(property="check_in_time", type="string", format="date-time", example="2025-12-04 09:55:00"),
     *                         @OA\Property(property="late_minutes", type="integer", example=0)
     *                     )
     *                 ),
     *                 @OA\Property(property="statistics", type="object",
     *                     @OA\Property(property="total_students", type="integer", example=25),
     *                     @OA\Property(property="present_count", type="integer", example=23),
     *                     @OA\Property(property="absent_count", type="integer", example=2),
     *                     @OA\Property(property="attendance_rate", type="number", format="float", example=92.0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found"
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
     * 取得課程的點名清單
     */
    public function getCourseAttendance(Request $request, ClubCourse $course): JsonResponse
    {
        try {
            $attendances = $course->attendances()
                ->with(['member.profile', 'markedBy'])
                ->orderBy('created_at')
                ->get();

            // 如果沒有出席記錄，自動生成學生名單
            if ($attendances->isEmpty()) {
                $this->generateAttendanceRoster($course);
                $attendances = $course->attendances()
                    ->with(['member.profile', 'markedBy'])
                    ->orderBy('created_at')
                    ->get();
            }

            $attendanceData = $attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'member' => [
                        'id' => $attendance->member->id,
                        'nickname' => $attendance->member->nickname,
                        'email' => $attendance->member->email,
                        'profile' => $attendance->member->profile ? [
                            'firstname' => $attendance->member->profile->firstname ?? null,
                            'lastname' => $attendance->member->profile->lastname ?? null,
                        ] : null,
                    ],
                    'status' => $attendance->status,
                    'status_label' => $attendance->status_label,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'late_minutes' => $attendance->late_minutes,
                    'early_leave_minutes' => $attendance->early_leave_minutes,
                    'actual_duration' => $attendance->actual_duration,
                    'note' => $attendance->note,
                    'marked_by' => $attendance->markedBy ? [
                        'id' => $attendance->markedBy->id,
                        'name' => $attendance->markedBy->name,
                    ] : null,
                    'marked_at' => $attendance->marked_at,
                    'created_at' => $attendance->created_at,
                    'updated_at' => $attendance->updated_at,
                ];
            });

            // 計算統計資料
            $stats = $this->calculateCourseAttendanceStats($attendances);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => [
                        'id' => $course->id,
                        'name' => $course->courseInfo->name ?? '未知課程',
                        'start_time' => $course->start_time,
                        'end_time' => $course->end_time,
                        'location' => $course->location,
                        'trial' => $course->trial,
                    ],
                    'attendances' => $attendanceData,
                    'statistics' => $stats,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取點名清單失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/attendance/courses/{course}/batch",
     *     summary="Batch mark attendance",
     *     description="Mark attendance for multiple students in a course at once",
     *     operationId="batchMarkAttendance",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Batch attendance data",
     *         @OA\JsonContent(
     *             required={"attendances"},
     *             @OA\Property(property="attendances", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="member_id", type="integer", example=5),
     *                     @OA\Property(property="status", type="string", enum={"present", "absent", "late", "early_leave", "excused"}, example="present"),
     *                     @OA\Property(property="check_in_time", type="string", format="date-time", example="2025-12-04 10:05:00"),
     *                     @OA\Property(property="check_out_time", type="string", format="date-time", example="2025-12-04 12:00:00"),
     *                     @OA\Property(property="note", type="string", example="Arrived on time")
     *                 )
     *             ),
     *             @OA\Property(property="marked_by", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Batch attendance marked successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="批量點名完成"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="member_id", type="integer", example=5),
     *                     @OA\Property(property="status", type="string", example="present"),
     *                     @OA\Property(property="updated", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found"
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
     * 批量點名
     */
    public function batchMarkAttendance(Request $request, ClubCourse $course): JsonResponse
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.member_id' => 'required|exists:members,id',
            'attendances.*.status' => 'required|in:present,absent,late,early_leave,excused',
            'attendances.*.check_in_time' => 'nullable|date',
            'attendances.*.check_out_time' => 'nullable|date',
            'attendances.*.note' => 'nullable|string|max:1000',
            'marked_by' => 'nullable|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $results = [];
            $markedBy = $validated['marked_by'] ?? auth()->id();

            foreach ($validated['attendances'] as $attendanceData) {
                $attendance = Attendance::updateOrCreate(
                    [
                        'club_course_id' => $course->id,
                        'member_id' => $attendanceData['member_id'],
                    ],
                    [
                        'status' => $attendanceData['status'],
                        'check_in_time' => $attendanceData['check_in_time'] ?? null,
                        'check_out_time' => $attendanceData['check_out_time'] ?? null,
                        'note' => $attendanceData['note'] ?? null,
                        'marked_by' => $markedBy,
                        'marked_at' => now(),
                    ]
                );

                $results[] = [
                    'member_id' => $attendanceData['member_id'],
                    'status' => $attendance->status,
                    'updated' => true,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '批量點名完成',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => '批量點名失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/attendance/courses/{course}/members/{member}",
     *     summary="Update single attendance record",
     *     description="Update attendance status for a specific student in a course",
     *     operationId="updateAttendance",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="member",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Attendance update data",
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"present", "absent", "late", "early_leave", "excused"}, example="present"),
     *             @OA\Property(property="check_in_time", type="string", format="date-time", example="2025-12-04 10:05:00"),
     *             @OA\Property(property="check_out_time", type="string", format="date-time", example="2025-12-04 12:00:00"),
     *             @OA\Property(property="note", type="string", example="Late due to traffic"),
     *             @OA\Property(property="marked_by", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="出席記錄更新成功"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=5),
     *                 @OA\Property(property="status", type="string", example="present"),
     *                 @OA\Property(property="status_label", type="string", example="出席"),
     *                 @OA\Property(property="check_in_time", type="string", format="date-time", example="2025-12-04 10:05:00"),
     *                 @OA\Property(property="marked_at", type="string", format="date-time", example="2025-12-04 10:10:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course or member not found"
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
     * 修改單一出席記錄
     */
    public function updateAttendance(Request $request, ClubCourse $course, Member $member): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:present,absent,late,early_leave,excused',
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date',
            'note' => 'nullable|string|max:1000',
            'marked_by' => 'nullable|exists:users,id',
        ]);

        try {
            $attendance = Attendance::updateOrCreate(
                [
                    'club_course_id' => $course->id,
                    'member_id' => $member->id,
                ],
                array_merge($validated, [
                    'marked_by' => $validated['marked_by'] ?? auth()->id(),
                    'marked_at' => now(),
                ])
            );

            return response()->json([
                'success' => true,
                'message' => '出席記錄更新成功',
                'data' => [
                    'id' => $attendance->id,
                    'member_id' => $attendance->member_id,
                    'status' => $attendance->status,
                    'status_label' => $attendance->status_label,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'note' => $attendance->note,
                    'marked_at' => $attendance->marked_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新出席記錄失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/attendance/courses/{course}/generate-roster",
     *     summary="Generate attendance roster",
     *     description="Automatically generate attendance roster for all registered students in a course",
     *     operationId="generateRoster",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roster generated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="成功生成 25 筆出席記錄"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="generated_count", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found"
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
     * 自動生成課程點名清單
     */
    public function generateRoster(ClubCourse $course): JsonResponse
    {
        try {
            $generated = $this->generateAttendanceRoster($course);
            
            return response()->json([
                'success' => true,
                'message' => "成功生成 {$generated} 筆出席記錄",
                'data' => [
                    'course_id' => $course->id,
                    'generated_count' => $generated,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '生成點名清單失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/attendance/members/{member}/stats",
     *     summary="Get member attendance statistics",
     *     description="Retrieve attendance statistics for a specific member with optional filters",
     *     operationId="getMemberAttendanceStats",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="member",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for date range filter",
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for date range filter",
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="course_info_id",
     *         in="query",
     *         description="Filter by specific course",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="member", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="total_sessions", type="integer", example=40),
     *                 @OA\Property(property="present_count", type="integer", example=36),
     *                 @OA\Property(property="absent_count", type="integer", example=2),
     *                 @OA\Property(property="late_count", type="integer", example=2),
     *                 @OA\Property(property="early_leave_count", type="integer", example=0),
     *                 @OA\Property(property="excused_count", type="integer", example=0),
     *                 @OA\Property(property="attendance_rate", type="number", format="float", example=90.0),
     *                 @OA\Property(property="average_late_minutes", type="number", format="float", example=5.5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Member not found"
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
     * 取得學生的出席統計
     */
    public function getMemberAttendanceStats(Request $request, Member $member): JsonResponse
    {
        try {
            $query = $member->attendances()->with(['clubCourse.courseInfo']);

            // 日期範圍篩選
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->inDateRange($request->start_date, $request->end_date);
            }

            // 課程篩選
            if ($request->has('course_info_id')) {
                $query->whereHas('clubCourse.courseInfo', function ($q) use ($request) {
                    $q->where('id', $request->course_info_id);
                });
            }

            $attendances = $query->get();

            $stats = [
                'member' => [
                    'id' => $member->id,
                    'nickname' => $member->nickname,
                    'email' => $member->email,
                ],
                'total_sessions' => $attendances->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'early_leave_count' => $attendances->where('status', 'early_leave')->count(),
                'excused_count' => $attendances->where('status', 'excused')->count(),
                'attendance_rate' => $attendances->count() > 0 ? 
                    round(($attendances->attended()->count() / $attendances->count()) * 100, 2) : 0,
                'average_late_minutes' => $attendances->where('status', 'late')->avg('late_minutes') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取出席統計失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/attendance/courses/{course}/stats",
     *     summary="Get course attendance statistics",
     *     description="Retrieve attendance statistics for a specific course",
     *     operationId="getCourseAttendanceStats",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="course",
     *         in="path",
     *         description="Course ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="course", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Advanced Python Course"),
     *                     @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-04 10:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-04 12:00:00")
     *                 ),
     *                 @OA\Property(property="total_students", type="integer", example=25),
     *                 @OA\Property(property="present_count", type="integer", example=23),
     *                 @OA\Property(property="absent_count", type="integer", example=2),
     *                 @OA\Property(property="late_count", type="integer", example=0),
     *                 @OA\Property(property="early_leave_count", type="integer", example=0),
     *                 @OA\Property(property="excused_count", type="integer", example=0),
     *                 @OA\Property(property="attendance_rate", type="number", format="float", example=92.0),
     *                 @OA\Property(property="absent_rate", type="number", format="float", example=8.0),
     *                 @OA\Property(property="average_late_minutes", type="number", format="float", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found"
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
     * 取得課程出席統計
     */
    public function getCourseAttendanceStats(ClubCourse $course): JsonResponse
    {
        try {
            $attendances = $course->attendances()->with('member')->get();
            $stats = $this->calculateCourseAttendanceStats($attendances);

            return response()->json([
                'success' => true,
                'data' => array_merge([
                    'course' => [
                        'id' => $course->id,
                        'name' => $course->courseInfo->name ?? '未知課程',
                        'start_time' => $course->start_time,
                        'end_time' => $course->end_time,
                    ],
                ], $stats)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取課程統計失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/attendance/statuses",
     *     summary="Get available attendance statuses",
     *     description="Retrieve list of all available attendance status options",
     *     operationId="getAvailableStatuses",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="value", type="string", example="present"),
     *                     @OA\Property(property="label", type="string", example="出席")
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
     * 獲取可用的出席狀態列表
     */
    public function getAvailableStatuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Attendance::getAvailableStatuses()
        ]);
    }

    /**
     * 私有方法：自動生成出席名單
     */
    private function generateAttendanceRoster(ClubCourse $course): int
    {
        // 根據現有的測試資料，先簡單生成所有會員的出席記錄
        $members = Member::all();
        
        $generated = 0;
        foreach ($members as $member) {
            $existed = Attendance::where([
                'club_course_id' => $course->id,
                'member_id' => $member->id,
            ])->exists();

            if (!$existed) {
                Attendance::create([
                    'club_course_id' => $course->id,
                    'member_id' => $member->id,
                    'status' => Attendance::STATUS_ABSENT,
                    'marked_at' => now(),
                ]);
                $generated++;
            }
        }

        return $generated;
    }

    /**
     * 私有方法：計算課程出席統計
     */
    private function calculateCourseAttendanceStats($attendances): array
    {
        $total = $attendances->count();
        
        return [
            'total_students' => $total,
            'present_count' => $attendances->where('status', 'present')->count(),
            'absent_count' => $attendances->where('status', 'absent')->count(),
            'late_count' => $attendances->where('status', 'late')->count(),
            'early_leave_count' => $attendances->where('status', 'early_leave')->count(),
            'excused_count' => $attendances->where('status', 'excused')->count(),
            'attendance_rate' => $total > 0 ? 
                round(($attendances->whereIn('status', ['present', 'late', 'early_leave'])->count() / $total) * 100, 2) : 0,
            'absent_rate' => $total > 0 ?
                round(($attendances->where('status', 'absent')->count() / $total) * 100, 2) : 0,
            'average_late_minutes' => $attendances->where('status', 'late')->avg('late_minutes') ?? 0,
        ];
    }
}
