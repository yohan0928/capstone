<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\Customer\BookingEndReminderNotification;
use App\Notifications\Customer\BookingStartReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send booking reminders to customers';

    public function handle()
    {
        $this->info('Starting booking reminder notifications...');
        $now = Carbon::now();
        $this->info("Current time: {$now->format('Y-m-d H:i:s')} ({$now->format('g:i A')})");

        // Send 2-hour start reminders
        $startCount = $this->sendStartReminders($now);

        // Send 10-minute end reminders
        $endCount = $this->sendEndReminders($now);

        $this->info('Booking reminder notifications completed.');
        $this->info("Start reminders sent: {$startCount}");
        $this->info("End reminders sent: {$endCount}");

        return Command::SUCCESS;
    }

    protected function sendStartReminders(Carbon $now)
    {
        $this->info('=== Checking for 2-hour start reminders ===');

        // Get ALL active bookings that haven't had start reminders sent
        $bookings = Booking::where('booking_status', 1)
            ->where('start_reminder_sent', false)
            ->whereNotNull('date_start')
            ->whereNotNull('start_time')
            ->with(['customerAccount', 'serviceName', 'branch'])
            ->get();

        $this->info("Total active bookings without start reminder: {$bookings->count()}");

        $filteredBookings = $bookings->filter(function ($booking) use ($now) {
            try {
                $this->info("Checking booking {$booking->booking_ref_no}:");

                // Parse using correct format - ensure 24-hour with seconds
                $bookingStart = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $booking->date_start . ' ' . $booking->start_time
                );

                // Calculate EXACT 2-hour reminder time
                $exactReminderTime = $bookingStart->copy()->subHours(2);

                // Debug with full date-time context
                $this->info("  Booking start: {$bookingStart->format('Y-m-d H:i:s')}");
                $this->info("  Exact reminder time: {$exactReminderTime->format('Y-m-d H:i:s')}");
                $this->info("  Current time: {$now->format('Y-m-d H:i:s')}");

                // Calculate difference in seconds for more precision
                $secondsDifference = $now->diffInSeconds($exactReminderTime, false);

                $this->info("  Time difference: {$secondsDifference} seconds");

                // Use a tighter window: ±30 seconds instead of ±60 seconds
                $shouldSend = $now->greaterThanOrEqualTo($exactReminderTime);

                if ($shouldSend) {
                    $this->info("  ✅ MATCH! Sending start reminder...");
                } else {
                    if ($secondsDifference > 0) {
                        $this->info("  ⏳ Too early - will send in " . gmdate('H:i:s', $secondsDifference));
                    } else {
                        $this->info("  ❌ Too late - missed by " . gmdate('H:i:s', abs($secondsDifference)));
                    }
                }

                return $shouldSend;

            } catch (\Exception $e) {
                $this->error("  ❌ Error parsing booking {$booking->id}: " . $e->getMessage());
                return false;
            }
        });

        $this->info("Matching bookings for 2-hour reminder: {$filteredBookings->count()}");

        $smsCount = 0;
        foreach ($filteredBookings as $booking) {
            try {
                $customer = $booking->customerAccount;
                
                // FIX: Mark as sent BEFORE notifying to prevent infinite loops if notification fails (sync driver)
                // This implements "At-Most-Once" delivery
                $booking->update([
                    'start_reminder_sent' => true,
                    'start_reminder_sent_at' => $now,
                ]);

                if ($customer) {
                    $notification = new BookingStartReminderNotification($booking);

                    // DEBUG: Check what channels will be used
                    $channels = $notification->via($customer);
                    $this->info("  Notification channels: " . implode(', ', $channels));

                    // Send notification
                    $customer->notify($notification);

                    // Check if SMS would be sent
                    if (in_array('sms', $channels)) {
                        $smsCount++;
                        $this->info("   📱 SMS queued for customer: {$customer->contact_no}");
                    }

                    $this->info("   ✅ Start reminder queued for: {$booking->booking_ref_no}");

                    // DEBUG: Test SMS directly
                    if ($customer->contact_no) {
                        $this->info("   📞 Customer phone number: {$customer->contact_no}");
                        // You could add direct SMS test here
                    }
                }

            } catch (\Exception $e) {
                $this->error('❌ Error: ' . $e->getMessage());
                Log::error('Failed to send start reminder for booking ' . $booking->id, [
                    'error' => $e->getMessage(),
                    'booking' => $booking->toArray()
                ]);
                // Note: Booking is already marked as sent, so we won't retry indefinitely
            }
        }

        if ($smsCount > 0) {
            $this->info("Total SMS start reminders queued: {$smsCount}");
        }

        return $filteredBookings->count();
    }

    protected function sendEndReminders(Carbon $now)
    {
        $this->info('=== Checking for 10-minute end reminders ===');

        // Get ALL active bookings that haven't had end reminders sent
        $bookings = Booking::where('booking_status', 1)
            ->where('end_reminder_sent', false)
            ->where(function ($query) {
                // Has either regular or extended end time
                $query->where(function ($q) {
                    $q
                        ->whereNotNull('date_end')
                        ->whereNotNull('end_time');
                })->orWhere(function ($q) {
                    $q
                        ->whereNotNull('extended_date_end')
                        ->whereNotNull('extended_end_time');
                });
            })
            ->with(['customerAccount', 'serviceName', 'branch'])
            ->get();

        $this->info("Total active bookings without end reminder: {$bookings->count()}");

        $filteredBookings = $bookings->filter(function ($booking) use ($now) {
            try {
                // Determine which end time to use
                if ($booking->extended_end_time && $booking->extended_date_end) {
                    // Use extended time
                    $endDate = $booking->extended_date_end;
                    $endTime = $booking->extended_end_time;
                    $isExtended = true;
                } else {
                    // Use regular time
                    $endDate = $booking->date_end;
                    $endTime = $booking->end_time;
                    $isExtended = false;
                }

                $this->info("Checking booking {$booking->booking_ref_no}:");
                $this->info("  End Date: {$endDate}");
                $this->info("  End Time: {$endTime}");
                $this->info('  Type: ' . ($isExtended ? 'Extended' : 'Regular'));

                // Parse using 24-hour format WITH seconds (H:i:s)
                $bookingEnd = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $endDate . ' ' . $endTime
                );

                // Calculate when the 10-minute reminder should be sent
                $reminderTime = $bookingEnd->copy()->subMinutes(10);

                // Use a 2-minute window (±1 minute) around the reminder time
                $windowStart = $reminderTime->copy()->subMinute();
                $windowEnd = $reminderTime->copy()->addMinute();

                $this->info("  Booking ends at: {$bookingEnd->format('g:i A')}");
                $this->info("  Reminder should send at: {$reminderTime->format('g:i A')}");
                $this->info("  Window: {$windowStart->format('g:i A')} to {$windowEnd->format('g:i A')}");
                $this->info("  Current time: {$now->format('g:i A')}");

                // Check if current time is within the window
                $reminderTime = $bookingEnd->copy()->subMinutes(10);

                // Send if now is equal or after the reminder time
                $shouldSend = $now->greaterThanOrEqualTo($reminderTime);


                if ($shouldSend) {
                    $this->info('  ✅ FOUND! Sending end reminder...');
                } else {
                    $timeUntil = $now->diffInMinutes($windowStart, false);
                    if ($timeUntil > 0) {
                        $this->info("  ⏳ Will send in {$timeUntil} minutes");
                    } else {
                        $this->info('  ❌ Missed window by ' . abs($timeUntil) . ' minutes');
                    }
                }

                return $shouldSend;
            } catch (\Exception $e) {
                $this->error("  ❌ Error parsing booking {$booking->id}: " . $e->getMessage());
                return false;
            }
        });

        $this->info("Matching bookings for 10-minute reminder: {$filteredBookings->count()}");

        $smsCount = 0;
        foreach ($filteredBookings as $booking) {
            try {
                $customer = $booking->customerAccount;

                // FIX: Mark as sent BEFORE notifying
                $booking->update([
                    'end_reminder_sent' => true,
                    'end_reminder_sent_at' => $now,
                ]);

                if ($customer) {
                    $notification = new BookingEndReminderNotification($booking);

                    // Send notification
                    $customer->notify($notification);

                    // Check if SMS would be sent
                    $channels = $notification->via($customer);
                    if (in_array('sms', $channels)) {
                        $smsCount++;
                        $this->info("   📱 SMS queued for customer: {$customer->contact_no}");
                    }

                    $this->info("   ✅ End reminder queued for: {$booking->booking_ref_no}");
                }

            } catch (\Exception $e) {
                $this->error('❌ Error: ' . $e->getMessage());
                Log::error('Failed to send end reminder for booking ' . $booking->id, [
                    'error' => $e->getMessage(),
                    'booking' => $booking->toArray()
                ]);
            }
        }

        if ($smsCount > 0) {
            $this->info("Total SMS end reminders queued: {$smsCount}");
        }

        return $filteredBookings->count();
    }
}