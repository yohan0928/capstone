<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Closure;

class RunBookingReminders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only run on main page requests to avoid running on AJAX/asset requests
        if ($this->shouldRunReminders($request)) {
            $this->checkAndRunReminders();
        }

        return $next($request);
    }

    /**
     * Check if we should run reminders on this request
     */
    protected function shouldRunReminders(Request $request)
    {
        // Only run on GET requests to main pages (not API/AJAX)
        if (!$request->isMethod('get')) {
            return false;
        }

        // Skip for common asset paths
        $skipPaths = [
            'api/*',
            'ajax/*',
            'css/*',
            'js/*',
            'img/*',
            'images/*',
            'fonts/*',
            'storage/*',
            'vendor/*',
            'node_modules/*',
        ];

        foreach ($skipPaths as $path) {
            if ($request->is($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check and run booking reminders if needed
     */
    protected function checkAndRunReminders()
    {
        $cacheKey = 'last_reminder_run';

        $secondsToWait = 10;

        $lastRun = Cache::get($cacheKey);

        if (!$lastRun || now()->diffInSeconds($lastRun) > $secondsToWait) {
            // Run reminders
            try {
                Log::info('Auto: Running booking reminders', ['time' => now()->toDateTimeString()]);

                \Artisan::call('bookings:send-reminders');
                $output = \Artisan::output();

                if (strpos($output, 'Found') !== false || strpos($output, 'Sent') !== false) {
                    Log::info('Auto: Booking reminders output', ['output' => trim($output)]);
                }

                $cacheExpiry = $secondsToWait + 10;  // ⬅️ CHANGE THIS (65 currently)
                Cache::put($cacheKey, now(), $cacheExpiry);
            } catch (\Exception $e) {
                Log::error('Auto: Failed to run booking reminders', [
                    'error' => $e->getMessage(),
                    'time' => now()->toDateTimeString(),
                ]);
            }
        }
    }
}
