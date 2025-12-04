<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\ClubCourse;
use App\Models\CounselingAppointment;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Member Schedule",
 *     description="Member schedule and calendar management"
 * )
 */
class MemberScheduleController extends Controller
{
    /**
     * Get member's comprehensive schedule
     *
     * @OA\Get(
     *     path="/members/{id}/schedule",
     *     summary="Get member's comprehensive schedule",
     *     description="Retrieve a member's schedule including courses and counseling appointments across different roles",
     *     operationId="getMemberSchedule",
     *     tags={"Member Schedule"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for schedule (defaults to start of current week)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for schedule (defaults to end of current week)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-07")
     *     ),
     *     @OA\Parameter(
     *         name="roles",
     *         in="query",
     *         description="Comma-separated list of roles to filter (student,teacher,assistant,counselor)",
     *         required=false,
     *         @OA\Schema(type="string", example="student,teacher")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member schedule retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="member_name", type="string", example="John Doe"),
     *             @OA\Property(
     *                 property="active_roles",
     *                 type="array",
     *                 @OA\Items(type="string", example="student")
     *             ),
     *             @OA\Property(
     *                 property="schedule",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="date", type="string", format="date", example="2024-01-01"),
     *                     @OA\Property(
     *                         property="events",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="string", example="course_123"),
     *                             @OA\Property(property="type", type="string", enum={"course", "counseling"}, example="course"),
     *                             @OA\Property(property="title", type="string", example="Programming 101"),
     *                             @OA\Property(property="start_time", type="string", format="time", example="14:00:00"),
     *                             @OA\Property(property="end_time", type="string", format="time", example="16:00:00"),
     *                             @OA\Property(property="role_in_event", type="string", enum={"student", "teacher", "assistant", "counselor"}, example="student"),
     *                             @OA\Property(property="status", type="string", example="confirmed")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Member not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getSchedule($memberId, Request $request)
    {
        $member = Member::findOrFail($memberId);
        
        // 驗證請求參數
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'roles' => 'nullable|string', // 以逗號分隔的角色列表
        ]);

        $startDate = $request->input('start_date', Carbon::now()->startOfWeek()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfWeek()->toDateString());
        $roleFilter = $request->input('roles') ? explode(',', $request->input('roles')) : [];

        // 獲取所有事件
        $events = [];
        
        // 1. 獲取課程相關事件
        if (empty($roleFilter) || array_intersect(['student', 'teacher', 'assistant'], $roleFilter)) {
            $courseEvents = $this->getCourseEvents($member, $startDate, $endDate, $roleFilter);
            $events = array_merge($events, $courseEvents);
        }
        
        // 2. 獲取諮商相關事件  
        if (empty($roleFilter) || array_intersect(['student', 'counselor'], $roleFilter)) {
            $counselingEvents = $this->getCounselingEvents($member, $startDate, $endDate, $roleFilter);
            $events = array_merge($events, $counselingEvents);
        }

        // 3. 按日期分組並格式化
        $schedule = $this->formatSchedule($member, $events);

        return response()->json($schedule);
    }

    /**
     * 獲取課程相關事件
     */
    private function getCourseEvents(Member $member, $startDate, $endDate, $roleFilter)
    {
        $events = [];

        // 作為學生的課程（通過購買記錄）
        if (empty($roleFilter) || in_array('student', $roleFilter)) {
            $studentCourses = ClubCourse::join('club_course_infos', 'club_courses.course_id', '=', 'club_course_infos.id')
                ->join('order_items', 'club_course_infos.product_id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.member_id', $member->id)
                ->where('orders.status', 'completed')
                ->whereBetween('club_courses.start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select(
                    'club_courses.*',
                    'club_course_infos.name as course_name',
                    'club_course_infos.description as course_description'
                )
                ->get();

            foreach ($studentCourses as $course) {
                $events[] = [
                    'id' => 'course_' . $course->id,
                    'type' => 'course',
                    'sub_type' => $course->trial ? 'trial' : 'regular',
                    'title' => $course->course_name,
                    'description' => $course->course_description,
                    'start_time' => Carbon::parse($course->start_time),
                    'end_time' => Carbon::parse($course->end_time),
                    'location' => $course->location ?: '線上',
                    'link' => $course->link,
                    'role_in_event' => 'student',
                    'status' => 'confirmed',
                    'course_info' => [
                        'course_id' => $course->course_id,
                        'club_course_id' => $course->id,
                        'is_trial' => $course->trial,
                    ]
                ];
            }
        }

        // 作為教師的課程
        if (empty($roleFilter) || in_array('teacher', $roleFilter)) {
            $teacherCourses = ClubCourse::join('club_course_infos', 'club_courses.course_id', '=', 'club_course_infos.id')
                ->join('teacher_club_course_info', 'club_course_infos.id', '=', 'teacher_club_course_info.club_course_info_id')
                ->where('teacher_club_course_info.member_id', $member->id)
                ->whereBetween('club_courses.start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select(
                    'club_courses.*',
                    'club_course_infos.name as course_name',
                    'club_course_infos.description as course_description'
                )
                ->get();

            foreach ($teacherCourses as $course) {
                $events[] = [
                    'id' => 'course_' . $course->id,
                    'type' => 'course',
                    'sub_type' => $course->trial ? 'trial' : 'regular',
                    'title' => $course->course_name,
                    'description' => $course->course_description,
                    'start_time' => Carbon::parse($course->start_time),
                    'end_time' => Carbon::parse($course->end_time),
                    'location' => $course->location ?: '線上',
                    'link' => $course->link,
                    'role_in_event' => 'teacher',
                    'status' => 'confirmed',
                    'course_info' => [
                        'course_id' => $course->course_id,
                        'club_course_id' => $course->id,
                        'is_trial' => $course->trial,
                    ]
                ];
            }
        }

        // 作為助教的課程
        if (empty($roleFilter) || in_array('assistant', $roleFilter)) {
            $assistantCourses = ClubCourse::join('club_course_infos', 'club_courses.course_id', '=', 'club_course_infos.id')
                ->join('assistant_club_course_info', 'club_course_infos.id', '=', 'assistant_club_course_info.club_course_info_id')
                ->where('assistant_club_course_info.member_id', $member->id)
                ->whereBetween('club_courses.start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select(
                    'club_courses.*',
                    'club_course_infos.name as course_name',
                    'club_course_infos.description as course_description'
                )
                ->get();

            foreach ($assistantCourses as $course) {
                $events[] = [
                    'id' => 'course_' . $course->id,
                    'type' => 'course',
                    'sub_type' => $course->trial ? 'trial' : 'regular',
                    'title' => $course->course_name,
                    'description' => $course->course_description,
                    'start_time' => Carbon::parse($course->start_time),
                    'end_time' => Carbon::parse($course->end_time),
                    'location' => $course->location ?: '線上',
                    'link' => $course->link,
                    'role_in_event' => 'assistant',
                    'status' => 'confirmed',
                    'course_info' => [
                        'course_id' => $course->course_id,
                        'club_course_id' => $course->id,
                        'is_trial' => $course->trial,
                    ]
                ];
            }
        }

        return $events;
    }

    /**
     * 獲取諮商相關事件
     */
    private function getCounselingEvents(Member $member, $startDate, $endDate, $roleFilter)
    {
        $events = [];

        // 作為學生的諮商預約
        if (empty($roleFilter) || in_array('student', $roleFilter)) {
            $studentAppointments = CounselingAppointment::with(['counselingInfo', 'counselor'])
                ->where('student_id', $member->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('preferred_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                          ->orWhereBetween('confirmed_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                })
                ->get();

            foreach ($studentAppointments as $appointment) {
                $appointmentTime = $appointment->confirmed_datetime ?: $appointment->preferred_datetime;
                $startTime = Carbon::parse($appointmentTime);
                $endTime = $startTime->copy()->addMinutes($appointment->duration);

                $events[] = [
                    'id' => 'counseling_' . $appointment->id,
                    'type' => 'counseling',
                    'sub_type' => $appointment->type,
                    'title' => $appointment->title,
                    'description' => $appointment->description,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'location' => $appointment->location ?: ($appointment->method === 'online' ? '線上' : 'TBD'),
                    'link' => $appointment->meeting_url,
                    'role_in_event' => 'student',
                    'status' => $appointment->status,
                    'counseling_info' => [
                        'appointment_id' => $appointment->id,
                        'counselor_name' => $appointment->counselor->nickname ?? 'TBD',
                        'service_name' => $appointment->counselingInfo->name ?? 'TBD',
                        'method' => $appointment->method,
                        'is_urgent' => $appointment->is_urgent,
                    ]
                ];
            }
        }

        // 作為諮商師的諮商預約
        if (empty($roleFilter) || in_array('counselor', $roleFilter)) {
            $counselorAppointments = CounselingAppointment::with(['counselingInfo', 'student'])
                ->where('counselor_id', $member->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('preferred_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                          ->orWhereBetween('confirmed_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                })
                ->get();

            foreach ($counselorAppointments as $appointment) {
                $appointmentTime = $appointment->confirmed_datetime ?: $appointment->preferred_datetime;
                $startTime = Carbon::parse($appointmentTime);
                $endTime = $startTime->copy()->addMinutes($appointment->duration);

                $events[] = [
                    'id' => 'counseling_' . $appointment->id,
                    'type' => 'counseling',
                    'sub_type' => $appointment->type,
                    'title' => $appointment->title,
                    'description' => $appointment->description,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'location' => $appointment->location ?: ($appointment->method === 'online' ? '線上' : 'TBD'),
                    'link' => $appointment->meeting_url,
                    'role_in_event' => 'counselor',
                    'status' => $appointment->status,
                    'counseling_info' => [
                        'appointment_id' => $appointment->id,
                        'student_name' => $appointment->student->nickname ?? 'TBD',
                        'service_name' => $appointment->counselingInfo->name ?? 'TBD',
                        'method' => $appointment->method,
                        'is_urgent' => $appointment->is_urgent,
                    ]
                ];
            }
        }

        return $events;
    }

    /**
     * 格式化時間表數據
     */
    private function formatSchedule(Member $member, $events)
    {
        // 按日期分組
        $groupedEvents = [];
        foreach ($events as $event) {
            $date = $event['start_time']->toDateString();
            if (!isset($groupedEvents[$date])) {
                $groupedEvents[$date] = [];
            }
            
            // 轉換時間格式為字符串
            $event['start_time'] = $event['start_time']->format('H:i:s');
            $event['end_time'] = $event['end_time']->format('H:i:s');
            
            $groupedEvents[$date][] = $event;
        }

        // 對每一天的事件按開始時間排序
        foreach ($groupedEvents as $date => $dayEvents) {
            usort($groupedEvents[$date], function($a, $b) {
                return strcmp($a['start_time'], $b['start_time']);
            });
        }

        // 格式化最終輸出
        $schedule = [];
        ksort($groupedEvents); // 按日期排序

        foreach ($groupedEvents as $date => $dayEvents) {
            $schedule[] = [
                'date' => $date,
                'events' => $dayEvents
            ];
        }

        // 獲取用戶的角色（基於現有的關聯關係推斷）
        $activeRoles = $this->getUserActiveRoles($member);

        return [
            'member_id' => $member->id,
            'member_name' => $member->nickname,
            'active_roles' => $activeRoles,
            'schedule' => $schedule
        ];
    }

    /**
     * 推斷用戶的活躍角色
     */
    private function getUserActiveRoles(Member $member)
    {
        $roles = [];

        // 檢查是否有購買課程記錄（學生）
        $hasOrders = $member->orders()->where('status', 'completed')->exists();
        if ($hasOrders) {
            $roles[] = 'student';
        }

        // 檢查是否為教師
        $isTeacher = \DB::table('teacher_club_course_info')
            ->where('member_id', $member->id)
            ->exists();
        if ($isTeacher) {
            $roles[] = 'teacher';
        }

        // 檢查是否為助教
        $isAssistant = \DB::table('assistant_club_course_info')
            ->where('member_id', $member->id)
            ->exists();
        if ($isAssistant) {
            $roles[] = 'assistant';
        }

        // 檢查是否為諮商師
        $isCounselor = \DB::table('counseling_info_counselors')
            ->where('counselor_id', $member->id)
            ->exists();
        if ($isCounselor) {
            $roles[] = 'counselor';
        }

        // 檢查是否有諮商預約作為學生
        $hasAppointments = CounselingAppointment::where('student_id', $member->id)->exists();
        if ($hasAppointments && !in_array('student', $roles)) {
            $roles[] = 'student';
        }

        return array_unique($roles);
    }
}