<?php

namespace App\Http\Controllers;

use App\Models\FlipCourseCase;
use App\Models\Prescription;
use App\Models\Assessment;
use App\Models\Task;
use App\Models\Order;
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
                $validated['counselor_id'],
                $case->id,
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
                $validated['analyst_id'],
                $case->id,
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

            $prescription = $this->workflowService->issuePrescription(
                $case,
                $validated['counseling_appointment_id'] ?? null,
                $validated['strategy_report'],
                $validated['counseling_notes'] ?? null,
                $validated['learning_goals'] ?? null,
                $validated['club_courses'] ?? [],
                $validated['learning_tasks'] ?? []
            );

            // Update case stage
            $case->update(['workflow_stage' => 'analyzing']);

            // Notify student
            $this->notificationService->createFlipPrescriptionIssuedNotification(
                $case->student_id,
                $prescription->id
            );

            DB::commit();

            return response()->json([
                'message' => 'Prescription issued successfully',
                'data' => $prescription->load(['learningTasks', 'clubCourses'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
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
                    $case->counselor_id,
                    $case->id,
                    $case->cycle_count
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
                    $case->student_id,
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
                $case->counselor_id,
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
