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
