<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\ClubCourse;
use App\Models\Notification;
use Carbon\Carbon;

class GenerateCourseReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:generate-course-reminders
                          {--hours=24 : Hours ahead to look for courses}
                          {--minutes-before=60 : Minutes before course to send reminder}
                          {--dry-run : Show what would be generated without actually creating notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Generate reminder notifications for upcoming courses';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursAhead = (int) $this->option('hours');
        $minutesBefore = (int) $this->option('minutes-before');
        $isDryRun = $this->option('dry-run');

        $this->info("Looking for courses starting within {$hoursAhead} hours...");
        $this->info("Will set reminders for {$minutesBefore} minutes before each course");

        // 查找即將開始的課程
        $upcomingCourses = ClubCourse::where('start_time', '>', now())
            ->where('start_time', '<=', now()->addHours($hoursAhead))
            ->whereDoesntHave('notifications', function($query) use ($minutesBefore) {
                $query->where('type', 'course_reminder')
                      ->where('data->minutes_before', $minutesBefore);
            })
            ->with('courseInfo')
            ->get();

        if ($upcomingCourses->isEmpty()) {
            $this->info('No upcoming courses found that need reminders');
            return;
        }

        $this->info("Found {$upcomingCourses->count()} courses that need reminders:");

        foreach ($upcomingCourses as $course) {
            $courseTime = Carbon::parse($course->start_time);
            $reminderTime = $courseTime->copy()->subMinutes($minutesBefore);

            $this->line("- Course: {$course->courseInfo->name}");
            $this->line("  Start Time: {$courseTime->format('Y-m-d H:i')}");
            $this->line("  Reminder Time: {$reminderTime->format('Y-m-d H:i')}");

            if (!$isDryRun) {
                try {
                    $this->notificationService->createCourseReminderNotifications(
                        $course->id, 
                        $minutesBefore
                    );
                    $this->line("  ✓ Reminder notifications created");
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to create reminders: " . $e->getMessage());
                }
            } else {
                $this->line("  (DRY RUN - Would create reminder notifications)");
            }

            $this->line('');
        }

        if ($isDryRun) {
            $this->info('DRY RUN completed. Use without --dry-run to actually create notifications.');
        } else {
            $this->info('Course reminder generation completed!');
        }
    }
}