<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\FlipCourseCase;
use App\Models\FlipCourseInfo;
use App\Models\Member;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FlipCourseWorkflowService
{
    /**
     * 1. 建立新案例（學生報名後）
     */
    public function createCase(
        FlipCourseInfo $flipCourseInfo,
        Member $student,
        Member $planner,
        ?Order $order = null
    ): FlipCourseCase {
        return DB::transaction(function () use ($flipCourseInfo, $student, $planner, $order) {
            // 建立案例
            $case = FlipCourseCase::create([
                'flip_course_info_id' => $flipCourseInfo->id,
                'student_id' => $student->id,
                'planner_id' => $planner->id,
                'order_id' => $order?->id,
                'workflow_stage' => 'planning',
                'payment_status' => $order ? 'pending' : 'confirmed',
            ]);

            // 建立規劃師的初始任務
            $this->createPlannerTasks($case);

            // 發送通知給規劃師
            // Notification::send($planner, new NewCaseAssignedNotification($case));

            return $case;
        });
    }

    /**
     * 2. 規劃師確認金流
     */
    public function confirmPayment(FlipCourseCase $case): void
    {
        DB::transaction(function () use ($case) {
            $case->update([
                'payment_status' => 'confirmed',
                'payment_confirmed_at' => now(),
            ]);

            // 完成「確認金流」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'confirm_payment')
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            // 通知規劃師金流已確認
            // Notification::send($case->planner, new PaymentConfirmedNotification($case));
        });
    }

    /**
     * 3. 規劃師建立 Line 群組
     */
    public function createLineGroup(FlipCourseCase $case, string $lineGroupUrl): void
    {
        DB::transaction(function () use ($case, $lineGroupUrl) {
            $case->update([
                'line_group_url' => $lineGroupUrl,
            ]);

            // 完成「建立 Line 群組」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'create_line_group')
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
        });
    }

    /**
     * 4. 規劃師指派諮商師
     */
    public function assignCounselor(FlipCourseCase $case, Member $counselor): void
    {
        DB::transaction(function () use ($case, $counselor) {
            $case->update([
                'counselor_id' => $counselor->id,
                'workflow_stage' => 'counseling',
                'started_at' => $case->started_at ?? now(),
            ]);

            // 完成規劃師的「指派諮商師」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'assign_counselor')
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            // 建立諮商師的初始任務
            $this->createCounselorTasks($case);

            // 發送通知給諮商師
            // Notification::send($counselor, new CaseAssignedNotification($case));
        });
    }

    /**
     * 5. 規劃師指派分析師
     */
    public function assignAnalyst(FlipCourseCase $case, Member $analyst): void
    {
        DB::transaction(function () use ($case, $analyst) {
            $case->update([
                'analyst_id' => $analyst->id,
            ]);

            // 完成規劃師的「指派分析師」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'assign_analyst')
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            // 發送通知給分析師
            // Notification::send($analyst, new AnalystAssignedNotification($case));
        });
    }

    /**
     * 6. 諮商師建立學習策略
     */
    public function createStrategy(FlipCourseCase $case, array $strategyData): Prescription
    {
        return DB::transaction(function () use ($case, $strategyData) {
            // 建立處方簽（草稿狀態）
            $prescription = Prescription::create([
                'flip_course_case_id' => $case->id,
                'counselor_id' => $case->counselor_id,
                'cycle_number' => $case->cycle_count + 1,
                'strategy_report' => $strategyData['strategy_report'],
                'counseling_notes' => $strategyData['counseling_notes'] ?? null,
                'learning_goals' => $strategyData['learning_goals'] ?? null,
                'status' => 'draft',
            ]);

            // 完成「建立學習策略」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'create_strategy')
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            return $prescription;
        });
    }

    /**
     * 7. 諮商師開立處方簽（包含派課程和學習任務）
     */
    public function issuePrescription(
        Prescription $prescription,
        array $clubCourseIds = [],
        array $learningTasks = []
    ): void {
        DB::transaction(function () use ($prescription, $clubCourseIds, $learningTasks) {
            $case = $prescription->flipCourseCase;

            // 開立處方簽
            $prescription->update([
                'status' => 'issued',
                'issued_at' => now(),
            ]);

            // 派發課程
            if (!empty($clubCourseIds)) {
                foreach ($clubCourseIds as $courseData) {
                    $prescription->clubCourses()->attach($courseData['id'], [
                        'reason' => $courseData['reason'] ?? null,
                        'recommended_sessions' => $courseData['recommended_sessions'] ?? 1,
                    ]);
                }
            }

            // 建立學習任務
            if (!empty($learningTasks)) {
                foreach ($learningTasks as $taskData) {
                    $prescription->learningTasks()->create($taskData);
                }
            }

            // 更新案例狀態
            $case->update([
                'workflow_stage' => 'analyzing',
            ]);

            // 完成諮商師的「開立處方簽」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'issue_prescription')
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            // 建立分析師的任務
            if ($case->analyst_id) {
                $this->createAnalystTasks($case, $prescription);

                // 發送通知給分析師
                // Notification::send($case->analyst, new PrescriptionIssuedNotification($prescription));
            }
        });
    }

    /**
     * 8. 分析師建立測驗
     */
    public function createAssessment(Prescription $prescription, array $assessmentData): Assessment
    {
        return DB::transaction(function () use ($prescription, $assessmentData) {
            $case = $prescription->flipCourseCase;

            $assessment = Assessment::create([
                'prescription_id' => $prescription->id,
                'analyst_id' => $case->analyst_id,
                'test_content' => $assessmentData['test_content'] ?? null,
                'analysis_report' => $assessmentData['analysis_report'] ?? '',
                'status' => 'draft',
            ]);

            // 完成「建立測驗」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'create_assessment')
                ->where('taskable_id', $prescription->id)
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            return $assessment;
        });
    }

    /**
     * 9. 分析師提交分析報告
     */
    public function submitAnalysis(Assessment $assessment, array $analysisData): void
    {
        DB::transaction(function () use ($assessment, $analysisData) {
            $prescription = $assessment->prescription;
            $case = $prescription->flipCourseCase;

            // 更新分析結果
            $assessment->update([
                'test_results' => $analysisData['test_results'] ?? null,
                'test_score' => $analysisData['test_score'] ?? null,
                'analysis_report' => $analysisData['analysis_report'],
                'metrics' => $analysisData['metrics'] ?? null,
                'recommendations' => $analysisData['recommendations'] ?? null,
                'study_hours' => $analysisData['study_hours'] ?? null,
                'tasks_completed' => $analysisData['tasks_completed'] ?? null,
                'courses_attended' => $analysisData['courses_attended'] ?? null,
                'status' => 'completed',
                'submitted_at' => now(),
                'completed_at' => now(),
            ]);

            // 完成分析師的「提交分析」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'submit_analysis')
                ->where('taskable_id', $assessment->id)
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            // 更新案例：進入循環階段
            $case->increment('cycle_count');
            $case->update([
                'workflow_stage' => 'cycling',
            ]);

            // 完成處方簽
            $prescription->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // 建立諮商師的「審查分析」任務
            Task::create([
                'flip_course_case_id' => $case->id,
                'assignee_id' => $case->counselor_id,
                'taskable_type' => Assessment::class,
                'taskable_id' => $assessment->id,
                'type' => 'review_analysis',
                'status' => 'pending',
                'priority' => 'high',
                'title' => "審查分析報告（循環 #{$case->cycle_count}）",
                'description' => "請審查分析師的分析報告，並決定是否需要調整學習策略。",
            ]);

            // 發送通知給諮商師
            // Notification::send($case->counselor, new AnalysisCompletedNotification($assessment));
        });
    }

    /**
     * 10. 諮商師審查分析並決定下一步
     */
    public function reviewAnalysisAndDecide(FlipCourseCase $case, bool $needsAnotherCycle): void
    {
        DB::transaction(function () use ($case, $needsAnotherCycle) {
            // 完成「審查分析」任務
            Task::where('flip_course_case_id', $case->id)
                ->where('type', 'review_analysis')
                ->where('status', '!=', 'completed')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            if ($needsAnotherCycle) {
                // 需要繼續循環：建立新的諮商任務
                $case->update([
                    'workflow_stage' => 'counseling',
                ]);

                $this->createCounselorTasks($case, isNewCycle: true);

                // 通知諮商師
                // Notification::send($case->counselor, new NewCycleStartedNotification($case));
            } else {
                // 完成整個案例
                $this->completeCase($case);
            }
        });
    }

    /**
     * 11. 完成案例
     */
    public function completeCase(FlipCourseCase $case): void
    {
        DB::transaction(function () use ($case) {
            $case->update([
                'workflow_stage' => 'completed',
                'completed_at' => now(),
            ]);

            // 取消所有未完成的任務
            Task::where('flip_course_case_id', $case->id)
                ->whereIn('status', ['pending', 'in_progress', 'blocked'])
                ->update([
                    'status' => 'cancelled',
                ]);

            // 發送完成通知給所有相關人員
            // Notification::send([$case->student, $case->counselor, $case->analyst, $case->planner],
            //     new CaseCompletedNotification($case));
        });
    }

    /**
     * 12. 取消案例
     */
    public function cancelCase(FlipCourseCase $case, string $reason): void
    {
        DB::transaction(function () use ($case, $reason) {
            $case->update([
                'workflow_stage' => 'cancelled',
            ]);

            // 取消所有未完成的任務
            Task::where('flip_course_case_id', $case->id)
                ->whereIn('status', ['pending', 'in_progress', 'blocked'])
                ->update([
                    'status' => 'cancelled',
                ]);

            // 記錄取消原因
            $case->notes()->create([
                'member_id' => auth()->id(),
                'note_type' => 'issue',
                'content' => "案例已取消。原因：{$reason}",
            ]);
        });
    }

    // ==========================================
    // 任務建立的輔助方法
    // ==========================================

    /**
     * 建立規劃師的任務
     */
    protected function createPlannerTasks(FlipCourseCase $case): void
    {
        $tasks = [
            [
                'type' => 'create_line_group',
                'title' => '建立 Line 群組',
                'description' => "為學生「{$case->student->name}」建立 Line 群組，並邀請相關人員加入。",
                'priority' => 'high',
            ],
            [
                'type' => 'confirm_payment',
                'title' => '確認金流',
                'description' => '確認學生的付款狀態。',
                'priority' => 'high',
            ],
            [
                'type' => 'assign_counselor',
                'title' => '指派諮商師',
                'description' => '為此案例指派適合的諮商師。',
                'priority' => 'normal',
            ],
            [
                'type' => 'assign_analyst',
                'title' => '指派分析師',
                'description' => '為此案例指派適合的分析師。',
                'priority' => 'normal',
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::create([
                'flip_course_case_id' => $case->id,
                'assignee_id' => $case->planner_id,
                'taskable_type' => FlipCourseCase::class,
                'taskable_id' => $case->id,
                'type' => $taskData['type'],
                'status' => 'pending',
                'priority' => $taskData['priority'],
                'title' => $taskData['title'],
                'description' => $taskData['description'],
            ]);
        }
    }

    /**
     * 建立諮商師的任務
     */
    protected function createCounselorTasks(FlipCourseCase $case, bool $isNewCycle = false): void
    {
        $cycleText = $isNewCycle ? "（循環 #{$case->cycle_count}）" : '';

        $tasks = [
            [
                'type' => 'create_strategy',
                'title' => "建立學習策略{$cycleText}",
                'description' => '根據學生的背景和目標，建立個人化的學習策略報告。',
                'priority' => 'high',
            ],
            [
                'type' => 'conduct_counseling',
                'title' => "進行諮商{$cycleText}",
                'description' => '與學生進行諮商會議，了解其學習需求和困難。',
                'priority' => 'high',
            ],
            [
                'type' => 'issue_prescription',
                'title' => "開立處方簽{$cycleText}",
                'description' => '根據諮商結果，開立包含學習任務和課程的處方簽。',
                'priority' => 'normal',
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::create([
                'flip_course_case_id' => $case->id,
                'assignee_id' => $case->counselor_id,
                'taskable_type' => FlipCourseCase::class,
                'taskable_id' => $case->id,
                'type' => $taskData['type'],
                'status' => 'pending',
                'priority' => $taskData['priority'],
                'title' => $taskData['title'],
                'description' => $taskData['description'],
            ]);
        }
    }

    /**
     * 建立分析師的任務
     */
    protected function createAnalystTasks(FlipCourseCase $case, Prescription $prescription): void
    {
        $tasks = [
            [
                'type' => 'create_assessment',
                'title' => "建立測驗（循環 #{$prescription->cycle_number}）",
                'description' => '根據處方簽的內容，建立相應的測驗和評估項目。',
                'priority' => 'high',
            ],
            [
                'type' => 'analyze_results',
                'title' => "分析學習成果（循環 #{$prescription->cycle_number}）",
                'description' => '收集並分析學生的學習數據、測驗結果和課程參與情況。',
                'priority' => 'normal',
            ],
            [
                'type' => 'submit_analysis',
                'title' => "提交分析報告（循環 #{$prescription->cycle_number}）",
                'description' => '完成分析報告並提交給諮商師審查。',
                'priority' => 'normal',
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::create([
                'flip_course_case_id' => $case->id,
                'assignee_id' => $case->analyst_id,
                'taskable_type' => Prescription::class,
                'taskable_id' => $prescription->id,
                'type' => $taskData['type'],
                'status' => 'pending',
                'priority' => $taskData['priority'],
                'title' => $taskData['title'],
                'description' => $taskData['description'],
            ]);
        }
    }
}
