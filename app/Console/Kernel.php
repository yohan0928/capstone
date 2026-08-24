<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\SendBookingReminders::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run every minute to check for reminders
        $schedule->command('bookings:send-reminders')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Check for expired rewards daily at midnight
        $schedule->command('rewards:check-expired')->daily();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}