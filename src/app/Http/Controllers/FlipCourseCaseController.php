<?php

namespace App\Http\Controllers;

use App\Models\FlipCourseCase;
use App\Models\Prescription;
use App\Models\Assessment;
use App\Models\Task;
use App\Models\Order;
use App\Models\CounselingAppointment;
use App\Services\FlipCourseWorkflowService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DB;

class FlipCourseCaseController extends Controller
{
    protected FlipCourseWorkflowService $workflowService;
    protected NotificationService $notificationService;

    public function __construct(
        FlipCourseWorkflowService $workflowService,
        NotificationService $notificationService
    ) {
        $this->workflowService = $workflowService;
        $this->notificationService = $notificationService;
    }

    /**
     * @OA\Get(
     *     path="/api/flip-course-cases",
     *     summary="Get flip course cases list",
     *     description="Retrieve list of flip course cases with filtering and pagination",
     *     operationId="getFlipCourseCases",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="workflow_stage",
     *         in="query",
     *         description="Filter by workflow stage",
     *         @OA\Schema(type="string", enum={"planning", "counseling", "cycling", "completed", "cancelled"}, example="counseling")
     *     ),
     *     @OA\Parameter(
     *         name="payment_status",
     *         in="query",
     *         description="Filter by payment status",
     *         @OA\Schema(type="string", example="confirmed")
     *     ),
     *     @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         description="Filter by student ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="planner_id",
     *         in="query",
     *         description="Filter by planner ID",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="counselor_id",
     *         in="query",
     *         description="Filter by counselor ID",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="analyst_id",
     *         in="query",
     *         description="Filter by analyst ID",
     *         @OA\Schema(type="integer", example=4)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
     * Display a listing of flip course cases.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FlipCourseCase::with([
            'flipCourseInfo',
            'student',
            'planner',
            'counselor',
            'analyst',
            'order'
        ]);

        // Filter by workflow stage
        if ($request->has('workflow_stage')) {
            $query->where('workflow_stage', $request->workflow_stage);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by student
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by planner
        if ($request->has('planner_id')) {
            $query->where('planner_id', $request->planner_id);
        }

        // Filter by counselor
        if ($request->has('counselor_id')) {
            $query->where('counselor_id', $request->counselor_id);
        }

        // Filter by analyst
        if ($request->has('analyst_id')) {
            $query->where('analyst_id', $request->analyst_id);
        }

        $cases = $query->paginate($request->get('per_page', 15));

        return response()->json($cases);
    }

    /**
     * @OA\Get(
     *     path="/api/flip-course-cases/{id}",
     *     summary="Get flip course case details",
     *     description="Retrieve detailed information about a specific flip course case",
     *     operationId="getFlipCourseCase",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     *
     * Display the specified flip course case.
     */
    public function show(int $id): JsonResponse
    {
        $case = FlipCourseCase::with([
            'flipCourseInfo',
            'student',
            'planner',
            'counselor',
            'analyst',
            'order',
            'prescriptions.learningTasks',
            'prescriptions.clubCourses',
            'assessments',
            'tasks',
            'notes',
            'counselingAppointments'
        ])->findOrFail($id);

        return response()->json($case);
    }

    /**
     * ========================================
     * Phase 1: Planner Actions
     * ========================================
     */

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/confirm-payment",
     *     summary="Confirm payment (Planner)",
     *     description="Planner confirms payment and moves case to planning stage",
     *     operationId="confirmFlipCasePayment",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method"},
     *             @OA\Property(property="payment_method", type="string", example="credit_card"),
     *             @OA\Property(property="payment_note", type="string", example="Paid via Stripe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment confirmed successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Confirm payment for a flip course case.
     */
    public function confirmPayment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);

            // Update payment status
            $case->update([
                'payment_status' => 'confirmed',
                'payment_confirmed_at' => now(),
                'workflow_stage' => 'planning',
            ]);

            // Update order status to completed
            if ($case->order_id) {
                Order::where('id', $case->order_id)->update(['status' => 'completed']);
            }

            // Create note
            $case->notes()->create([
                'member_id' => $request->user()->id ?? $case->planner_id,
                'note_type' => 'planning',
                'content' => "金流確認完成\n付款方式: {$validated['payment_method']}\n備註: " . ($validated['payment_note'] ?? '無'),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Payment confirmed successfully',
                'data' => $case->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/create-line-group",
     *     summary="Create Line group (Planner)",
     *     description="Planner creates Line group for case communication",
     *     operationId="createFlipCaseLineGroup",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"line_group_url"},
     *             @OA\Property(property="line_group_url", type="string", format="uri", example="https://line.me/ti/g/ABC123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Line group created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Create Line group for the case.
     */
    public function createLineGroup(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'line_group_url' => 'required|url',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);

            $case->update([
                'line_group_url' => $validated['line_group_url'],
            ]);

            // Create note
            $case->notes()->create([
                'member_id' => $request->user()->id ?? $case->planner_id,
                'note_type' => 'planning',
                'content' => "Line 群組已建立\n連結: {$validated['line_group_url']}",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Line group created successfully',
                'data' => $case->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/assign-counselor",
     *     summary="Assign counselor (Planner)",
     *     description="Planner assigns counselor to the case",
     *     operationId="assignFlipCaseCounselor",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"counselor_id"},
     *             @OA\Property(property="counselor_id", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counselor assigned successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Assign counselor to the case.
     */
    public function assignCounselor(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'counselor_id' => 'required|exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);

            $case->update([
                'counselor_id' => $validated['counselor_id'],
            ]);

            // Create notification for counselor
            $this->notificationService->createFlipCaseAssignedNotification(
                $case->id,
                $validated['counselor_id'],
                'counselor'
            );

            // Create note
            $case->notes()->create([
                'member_id' => $request->user()->id ?? $case->planner_id,
                'note_type' => 'planning',
                'content' => "諮商師已指派: Member ID {$validated['counselor_id']}",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Counselor assigned successfully',
                'data' => $case->fresh(['counselor'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/assign-analyst",
     *     summary="Assign analyst (Planner)",
     *     description="Planner assigns analyst and moves case to counseling stage",
     *     operationId="assignFlipCaseAnalyst",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"analyst_id"},
     *             @OA\Property(property="analyst_id", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analyst assigned successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Assign analyst to the case.
     */
    public function assignAnalyst(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'analyst_id' => 'required|exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);

            $case->update([
                'analyst_id' => $validated['analyst_id'],
                'workflow_stage' => 'counseling',
            ]);

            // Create notification for analyst
            $this->notificationService->createFlipCaseAssignedNotification(
                $case->id,
                $validated['analyst_id'],
                'analyst'
            );

            // Create note
            $case->notes()->create([
                'member_id' => $request->user()->id ?? $case->planner_id,
                'note_type' => 'planning',
                'content' => "分析師已指派: Member ID {$validated['analyst_id']}",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Analyst assigned and case moved to counseling stage',
                'data' => $case->fresh(['analyst'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ========================================
     * Phase 2: Counselor Actions
     * ========================================
     */

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/schedule-counseling",
     *     summary="Schedule counseling meeting (Counselor)",
     *     description="Counselor schedules a counseling session for the case",
     *     operationId="scheduleFlipCaseCounseling",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"preferred_datetime"},
     *             @OA\Property(property="counseling_info_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Initial Consultation"),
     *             @OA\Property(property="preferred_datetime", type="string", format="date-time", example="2025-12-10 14:00:00"),
     *             @OA\Property(property="confirmed_datetime", type="string", format="date-time", example="2025-12-10 14:00:00"),
     *             @OA\Property(property="duration", type="integer", example=60),
     *             @OA\Property(property="method", type="string", enum={"online", "offline", "phone"}, example="online"),
     *             @OA\Property(property="meeting_url", type="string", format="uri", example="https://zoom.us/j/123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Counseling meeting scheduled successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Schedule counseling meeting.
     */
    public function scheduleCounseling(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'counseling_info_id' => 'nullable|exists:counseling_infos,id',
            'title' => 'nullable|string',
            'preferred_datetime' => 'required|date',
            'confirmed_datetime' => 'nullable|date',
            'duration' => 'nullable|integer',
            'method' => 'nullable|in:online,offline,phone',
            'meeting_url' => 'nullable|url',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);

            $appointment = $this->workflowService->scheduleCounselingMeeting($case, $validated);

            DB::commit();

            return response()->json([
                'message' => 'Counseling meeting scheduled successfully',
                'data' => $appointment
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/issue-prescription",
     *     summary="Issue prescription (Counselor)",
     *     description="Counselor issues learning prescription with courses and tasks",
     *     operationId="issueFlipCasePrescription",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"strategy_report"},
     *             @OA\Property(property="counseling_appointment_id", type="integer", example=1),
     *             @OA\Property(property="strategy_report", type="string", example="Comprehensive learning strategy report"),
     *             @OA\Property(property="counseling_notes", type="string", example="Session notes and observations"),
     *             @OA\Property(property="learning_goals", type="array", @OA\Items(type="string"), example={"Master Python basics", "Build portfolio projects"}),
     *             @OA\Property(property="club_courses", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="club_course_info_id", type="integer", example=1),
     *                     @OA\Property(property="reason", type="string", example="Strengthen foundational knowledge"),
     *                     @OA\Property(property="recommended_sessions", type="integer", example=8)
     *                 )
     *             ),
     *             @OA\Property(property="learning_tasks", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="title", type="string", example="Complete Python Tutorial"),
     *                     @OA\Property(property="description", type="string", example="Work through chapters 1-5"),
     *                     @OA\Property(property="resources", type="string", example="https://docs.python.org"),
     *                     @OA\Property(property="estimated_hours", type="integer", example=10),
     *                     @OA\Property(property="due_date", type="string", format="date", example="2025-12-20")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Prescription issued successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Issue prescription (create learning strategy).
     */
    public function issuePrescription(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'counseling_appointment_id' => 'nullable|exists:counseling_appointments,id',
            'strategy_report' => 'required|string',
            'counseling_notes' => 'nullable|string',
            'learning_goals' => 'nullable|array',
            'club_courses' => 'nullable|array',
            'club_courses.*.club_course_info_id' => 'required|exists:club_course_infos,id',
            'club_courses.*.reason' => 'nullable|string',
            'club_courses.*.recommended_sessions' => 'nullable|integer',
            'learning_tasks' => 'nullable|array',
            'learning_tasks.*.title' => 'required|string',
            'learning_tasks.*.description' => 'required|string',
            'learning_tasks.*.resources' => 'nullable|string',
            'learning_tasks.*.estimated_hours' => 'nullable|integer',
            'learning_tasks.*.due_date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);

            // Get counseling appointment if provided
            $counselingAppointment = null;
            if (isset($validated['counseling_appointment_id'])) {
                $counselingAppointment = CounselingAppointment::findOrFail($validated['counseling_appointment_id']);
            }

            // Step 1: Create prescription (strategy)
            $prescription = $this->workflowService->createStrategy(
                $case,
                [
                    'strategy_report' => $validated['strategy_report'],
                    'counseling_notes' => $validated['counseling_notes'] ?? null,
                    'learning_goals' => $validated['learning_goals'] ?? null,
                ],
                $counselingAppointment
            );

            // Step 2: Issue prescription with club courses and learning tasks
            // Transform club_courses to match expected format
            $clubCourses = [];
            if (!empty($validated['club_courses'])) {
                foreach ($validated['club_courses'] as $course) {
                    $clubCourses[] = [
                        'id' => $course['club_course_info_id'],
                        'reason' => $course['reason'] ?? null,
                        'recommended_sessions' => $course['recommended_sessions'] ?? 1,
                    ];
                }
            }

            $this->workflowService->issuePrescription(
                $prescription,
                $clubCourses,
                $validated['learning_tasks'] ?? []
            );

            // Notify student
            $this->notificationService->createFlipPrescriptionIssuedNotification(
                $prescription->id
            );

            DB::commit();

            return response()->json([
                'message' => 'Prescription issued successfully',
                'data' => $prescription->fresh(['learningTasks', 'clubCourses'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/review-analysis",
     *     summary="Review analysis (Counselor)",
     *     description="Counselor reviews analysis and decides to continue cycle or complete case",
     *     operationId="reviewFlipCaseAnalysis",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"assessment_id", "continue_cycle"},
     *             @OA\Property(property="assessment_id", type="integer", example=1),
     *             @OA\Property(property="continue_cycle", type="boolean", example=true),
     *             @OA\Property(property="review_notes", type="string", example="Good progress, continue with next cycle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analysis reviewed successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Review analysis and decide next action.
     */
    public function reviewAnalysis(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'continue_cycle' => 'required|boolean',
            'review_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);
            $assessment = Assessment::findOrFail($validated['assessment_id']);

            if ($validated['continue_cycle']) {
                // Start new cycle
                $case->update([
                    'cycle_count' => $case->cycle_count + 1,
                    'workflow_stage' => 'counseling',
                ]);

                // Notify counselor to start new cycle
                $this->notificationService->createFlipCycleStartedNotification(
                    $case->id
                );

                $message = 'New cycle started';
            } else {
                // Complete case
                $case->update([
                    'workflow_stage' => 'completed',
                    'completed_at' => now(),
                ]);

                // Notify student
                $this->notificationService->createFlipCaseCompletedNotification(
                    $case->id
                );

                $message = 'Case completed';
            }

            // Create note
            $case->notes()->create([
                'member_id' => $request->user()->id ?? $case->counselor_id,
                'note_type' => 'counseling',
                'content' => "分析報告審查完成\n" . ($validated['review_notes'] ?? ''),
            ]);

            DB::commit();

            return response()->json([
                'message' => $message,
                'data' => $case->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ========================================
     * Phase 3: Analyst Actions
     * ========================================
     */

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/create-assessment",
     *     summary="Create assessment (Analyst)",
     *     description="Analyst creates an assessment for a prescription",
     *     operationId="createFlipCaseAssessment",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prescription_id"},
     *             @OA\Property(property="prescription_id", type="integer", example=1),
     *             @OA\Property(property="test_content", type="string", example="Assessment questions and materials"),
     *             @OA\Property(property="test_results", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="test_score", type="integer", example=85)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assessment created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Create assessment for a prescription.
     */
    public function createAssessment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'prescription_id' => 'required|exists:prescriptions,id',
            'test_content' => 'nullable|string',
            'test_results' => 'nullable|array',
            'test_score' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);
            $prescription = Prescription::findOrFail($validated['prescription_id']);

            $assessment = Assessment::create([
                'prescription_id' => $prescription->id,
                'analyst_id' => $case->analyst_id,
                'test_content' => $validated['test_content'] ?? null,
                'test_results' => $validated['test_results'] ?? null,
                'test_score' => $validated['test_score'] ?? null,
                'status' => 'draft',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Assessment created successfully',
                'data' => $assessment
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flip-course-cases/{id}/submit-analysis",
     *     summary="Submit analysis report (Analyst)",
     *     description="Analyst submits completed analysis report and moves case to cycling stage",
     *     operationId="submitFlipCaseAnalysis",
     *     tags={"Flip Course Cases"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Flip course case ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"assessment_id", "analysis_report"},
     *             @OA\Property(property="assessment_id", type="integer", example=1),
     *             @OA\Property(property="analysis_report", type="string", example="Comprehensive learning analysis and recommendations"),
     *             @OA\Property(property="metrics", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="recommendations", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="study_hours", type="integer", example=25),
     *             @OA\Property(property="tasks_completed", type="integer", example=8),
     *             @OA\Property(property="courses_attended", type="integer", example=6)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analysis submitted successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Case not found"
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
     * Submit analysis report.
     */
    public function submitAnalysis(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'analysis_report' => 'required|string',
            'metrics' => 'nullable|array',
            'recommendations' => 'nullable|array',
            'study_hours' => 'nullable|integer',
            'tasks_completed' => 'nullable|integer',
            'courses_attended' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $case = FlipCourseCase::findOrFail($id);
            $assessment = Assessment::findOrFail($validated['assessment_id']);

            $assessment->update([
                'analysis_report' => $validated['analysis_report'],
                'metrics' => $validated['metrics'] ?? null,
                'recommendations' => $validated['recommendations'] ?? null,
                'study_hours' => $validated['study_hours'] ?? null,
                'tasks_completed' => $validated['tasks_completed'] ?? null,
                'courses_attended' => $validated['courses_attended'] ?? null,
                'status' => 'completed',
                'submitted_at' => now(),
                'completed_at' => now(),
            ]);

            // Update case stage
            $case->update(['workflow_stage' => 'cycling']);

            // Notify counselor
            $this->notificationService->createFlipAnalysisCompletedNotification(
                $assessment->id
            );

            DB::commit();

            return response()->json([
                'message' => 'Analysis submitted successfully',
                'data' => $assessment->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ========================================
     * Query Methods
     * ========================================
     */

    /**
     * Get all prescriptions for a case.
     */
    public function getPrescriptions(int $id): JsonResponse
    {
        $case = FlipCourseCase::findOrFail($id);
        $prescriptions = $case->prescriptions()
            ->with(['counselor', 'learningTasks', 'clubCourses', 'counselingAppointment'])
            ->orderBy('cycle_number', 'desc')
            ->get();

        return response()->json($prescriptions);
    }

    /**
     * Get all assessments for a case.
     */
    public function getAssessments(int $id): JsonResponse
    {
        $case = FlipCourseCase::findOrFail($id);
        $assessments = $case->assessments()
            ->with(['analyst', 'prescription'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($assessments);
    }

    /**
     * Get all tasks for a case.
     */
    public function getTasks(int $id): JsonResponse
    {
        $case = FlipCourseCase::findOrFail($id);
        $tasks = $case->tasks()
            ->with(['assignee', 'taskable'])
            ->orderBy('due_date', 'asc')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Get all notes for a case.
     */
    public function getNotes(int $id): JsonResponse
    {
        $case = FlipCourseCase::findOrFail($id);
        $notes = $case->notes()
            ->with('member')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notes);
    }

    /**
     * Get case statistics.
     */
    public function getStatistics(int $id): JsonResponse
    {
        $case = FlipCourseCase::findOrFail($id);

        $stats = [
            'cycle_count' => $case->cycle_count,
            'workflow_stage' => $case->workflow_stage,
            'payment_status' => $case->payment_status,
            'total_prescriptions' => $case->prescriptions()->count(),
            'total_assessments' => $case->assessments()->count(),
            'total_tasks' => $case->tasks()->count(),
            'completed_tasks' => $case->tasks()->where('status', 'completed')->count(),
            'pending_tasks' => $case->tasks()->where('status', 'pending')->count(),
            'counseling_appointments' => $case->counselingAppointments()->count(),
        ];

        return response()->json($stats);
    }
}
