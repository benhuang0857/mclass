<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:send 
                          {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send pending notifications to users';

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
        $this->info('Starting notification sending process...');

        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE - No notifications will actually be sent');
            $pendingCount = \App\Models\Notification::pending()->count();
            $this->info("Would send {$pendingCount} notifications");
            return;
        }

        $sentCount = $this->notificationService->sendPendingNotifications();
        
        if ($sentCount > 0) {
            $this->info("Successfully sent {$sentCount} notifications");
        } else {
            $this->info('No pending notifications to send');
        }
    }
}