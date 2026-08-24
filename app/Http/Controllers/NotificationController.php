<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CustomerAccount;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SmsService;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get pagination and filtering parameters
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 10);
        $tab = request()->get('tab', 'all');
        $offset = ($page - 1) * $limit;

        try {
            // Base query
            $notificationsQuery = $user->{'notifications'}()
                ->with(['notifiable']) // Eager load the notifiable relationship
                ->orderBy('created_at', 'desc');
            
            // Apply tab filtering
            if ($tab === 'unread') {
                $notificationsQuery->whereNull('read_at');
            } elseif ($tab === 'read') {
                $notificationsQuery->whereNotNull('read_at');
            }
            
            // Get total count for current tab
            $totalCount = $notificationsQuery->count();
            
            // Apply pagination
            $notifications = $notificationsQuery->skip($offset)->take($limit)->get();

            // Transform notifications for frontend
            $transformedNotifications = $notifications->map(function ($notification) {
                $data = $notification->data ?? [];
                
                // Add booking details if it's a booking reminder
                if (isset($data['type']) && str_contains($data['type'], 'booking_')) {
                    $data['is_booking_reminder'] = true;
                    
                    // Format the message if it's a booking reminder
                    if (isset($data['booking_id'])) {
                        try {
                            $booking = Booking::find($data['booking_id']);
                            if ($booking) {
                                $data['booking_ref_no'] = $booking->booking_ref_no;
                                $data['service_name'] = $booking->serviceName->service_name ?? 'N/A';
                                $data['branch_name'] = $booking->branch->branch_name ?? 'N/A';
                            }
                        } catch (\Exception $e) {
                            // Ignore if booking not found
                        }
                    }
                }
                
                return [
                    'id' => $notification->id,
                    'data' => $data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->toISOString(),
                    'type' => $data['type'] ?? 'general',
                    'action' => $data['action'] ?? null,
                ];
            });

            // Get total unread count (for badge)
            $unreadCount = $user->{'unreadNotifications'}()->count();

            return response()->json([
                'success' => true,
                'notifications' => $transformedNotifications,
                'total_count' => $totalCount,
                'unread_count' => $unreadCount,
                'has_more' => ($offset + $notifications->count()) < $totalCount,
                'current_page' => (int)$page,
                'page_size' => (int)$limit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch notifications',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            // Use dynamic property access
            $user->{'unreadNotifications'}->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark notifications as read'], 500);
        }
    }

    /**
     * Mark all read notifications as unread.
     */
    public function markAllAsUnread()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            // Use dynamic method call
            $user->{'notifications'}()->whereNotNull('read_at')->update(['read_at' => null]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as unread'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark notifications as unread'], 500);
        }
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead($id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $notification = $user->{'notifications'}()->find($id);
            if ($notification) {
                $notification->markAsRead();
                return response()->json(['success' => true]);
            }
            return response()->json(['error' => 'Notification not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark notification as read'], 500);
        }
    }

    /**
     * Mark a single notification as unread.
     */
    public function markAsUnread($id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $notification = $user->{'notifications'}()->find($id);
            if ($notification) {
                $notification->update(['read_at' => null]);
                return response()->json(['success' => true]);
            }
            return response()->json(['error' => 'Notification not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark notification as unread'], 500);
        }
    }

    /**
     * Helper to get the currently authenticated user from any guard.
     */
    private function getAuthenticatedUser()
    {
        foreach (['owner', 'staff', 'customer'] as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                return $user;
            }
        }
        return null;
    }

    /**
     * Get notification counts for the authenticated user.
     */
    public function getCounts()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $totalCount = $user->{'notifications'}()->count();
            $unreadCount = $user->{'unreadNotifications'}()->count();

            return response()->json([
                'success' => true,
                'total_count' => $totalCount,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get notification counts'], 500);
        }
    }

    /**
     * Get toast notifications from session (for unauthenticated users or login errors)
     */
    public function getSessionToasts()
    {
        $toasts = [];

        // Check for session errors (like Google login errors)
        if (session()->has('errors')) {
            foreach (session('errors')->all() as $error) {
                $toasts[] = [
                    'type' => 'error',
                    'message' => $error,
                    'action' => 'authentication_error',
                    'created_at' => now()->toISOString(),
                    'id' => 'session_error_' . uniqid(),
                    'is_session_toast' => true
                ];
            }
        }

        // Check for custom toast messages
        if (session()->has('toast_error')) {
            $toasts[] = [
                'type' => 'error',
                'message' => session('toast_error'),
                'action' => 'authentication_error',
                'created_at' => now()->toISOString(),
                'id' => 'session_toast_' . uniqid(),
                'is_session_toast' => true
            ];
        }

        return response()->json([
            'success' => true,
            'toast_notifications' => $toasts,
            'is_session_based' => true
        ]);
    }

    /**
     * Test notification for booking reminders
     */
    public function testBookingNotification()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Create a test booking reminder notification
        $notificationData = [
            'type' => 'booking_start_reminder',
            'title' => 'Booking Starting Soon',
            'message' => 'Your booking for VIP Massage starts in 2 hours.',
            'booking_id' => 1, // Example booking ID
            'booking_ref_no' => 'BK-2024-001',
            'service_name' => 'VIP Massage',
            'branch_name' => 'Main Branch',
            'time' => '3:00 PM',
            'date' => 'Dec 15, 2024',
            'url' => '/sub_three/my_bookings/details/test-uuid',
            'icon' => 'clock',
            'color' => 'blue',
            'action' => 'booking_reminder',
        ];

        // Create notification manually
        $user->{'notifications'}()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\Customer\BookingStartReminderNotification::class,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => $notificationData,
            'read_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test booking notification created'
        ]);
    }
}