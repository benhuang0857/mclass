<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})
    ->purpose("Display an inspiring quote")
    ->hourly();

Artisan::command('db:init-necessary', function () {
    $sqlFilePath = database_path('sqls/init_necessary.sql');

    if (!file_exists($sqlFilePath)) {
        $this->error("SQL file not found at: $sqlFilePath");
        return Command::FAILURE;
    }

    try {
        $sql = file_get_contents($sqlFilePath);
        DB::unprepared($sql);

        $this->info('Database necessary tables have been initialized successfully.');
        return Command::SUCCESS;
    } catch (\Exception $e) {
        $this->error('Failed to initialize database necessary tables: ' . $e->getMessage());
        return Command::FAILURE;
    }
})->describe('Initialize database necessary tables using predefined SQL files');

Artisan::command('db:init-combo', function () {
    try {
        // Step 1: Run db:init-necessary
        $this->info('Running db:init-necessary...');
        $result = Artisan::call('db:init-necessary');
        if ($result !== Command::SUCCESS) {
            $this->error('Failed to run db:init-necessary.');
            return Command::FAILURE;
        }
        $this->info('db:init-necessary completed successfully.');

        // Step 2: Run migrate
        $this->info('Running migrate...');
        $result = Artisan::call('migrate');
        if ($result !== Command::SUCCESS) {
            $this->error('Failed to run migrate.');
            return Command::FAILURE;
        }
        $this->info('migrate completed successfully.');

        // Step 3: Run db:seed
        $this->info('Running db:seed...');
        $result = Artisan::call('db:seed');
        if ($result !== Command::SUCCESS) {
            $this->error('Failed to run db:seed.');
            return Command::FAILURE;
        }
        $this->info('db:seed completed successfully.');

        // Step 4: Run ZoomCredentialSeeder
        $this->info('Running ZoomCredentialSeeder...');
        $result = Artisan::call('db:seed', ['--class' => 'ZoomCredentialSeeder']);
        if ($result !== Command::SUCCESS) {
            $this->error('Failed to run ZoomCredentialSeeder.');
            return Command::FAILURE;
        }
        $this->info('ZoomCredentialSeeder completed successfully.');

        $this->info('init_combo completed successfully.');
        return Command::SUCCESS;
    } catch (\Exception $e) {
        $this->error('Failed to initialize database: ' . $e->getMessage());
        $this->warn('Attempting to rollback migrations...');
        Artisan::call('migrate:rollback');
        return Command::FAILURE;
    }
})->describe('Run db:init-necessary, migrate, and db:seed to initialize the database');