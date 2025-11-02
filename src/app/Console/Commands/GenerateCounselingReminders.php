<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\CounselingAppointment;
use App\Models\Notification;
use Carbon\Carbon;

class GenerateCounselingReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:generate-counseling-reminders
                          {--hours=24 : Hours ahead to look for appointments}
                          {--minutes-before=60 : Minutes before appointment to send reminder}
                          {--dry-run : Show what would be generated without actually creating notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Generate reminder notifications for upcoming counseling appointments';

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

        $this->info("Looking for counseling appointments within {$hoursAhead} hours...");
        $this->info("Will set reminders for {$minutesBefore} minutes before each appointment");

        // 查找即將開始的諮商預約（已確認的）
        $upcomingAppointments = CounselingAppointment::where('status', 'confirmed')
            ->where(function($query) use ($hoursAhead) {
                $query->where('confirmed_datetime', '>', now())
                      ->where('confirmed_datetime', '<=', now()->addHours($hoursAhead));
            })
            ->whereDoesntHave('notifications', function($query) use ($minutesBefore) {
                $query->where('type', 'counseling_reminder')
                      ->where('data->minutes_before', $minutesBefore);
            })
            ->with(['student', 'counselor', 'counselingInfo'])
            ->get();

        if ($upcomingAppointments->isEmpty()) {
            $this->info('No upcoming confirmed counseling appointments found that need reminders');
            return;
        }

        $this->info("Found {$upcomingAppointments->count()} appointments that need reminders:");

        foreach ($upcomingAppointments as $appointment) {
            $appointmentTime = Carbon::parse($appointment->confirmed_datetime);
            $reminderTime = $appointmentTime->copy()->subMinutes($minutesBefore);

            $this->line("- Service: {$appointment->counselingInfo->name}");
            $this->line("  Student: {$appointment->student->nickname}");
            $this->line("  Counselor: {$appointment->counselor->nickname}");
            $this->line("  Appointment Time: {$appointmentTime->format('Y-m-d H:i')}");
            $this->line("  Reminder Time: {$reminderTime->format('Y-m-d H:i')}");

            if (!$isDryRun) {
                try {
                    $this->notificationService->createCounselingReminderNotifications(
                        $appointment->id,
                        $minutesBefore
                    );
                    $this->line("  ✓ Reminder notifications created for both student and counselor");
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
            $this->info('Counseling reminder generation completed!');
        }
    }
}