<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get notifications for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $unreadOnly = $request->input('unread_only', false);
            
            $query = Notification::where(function ($q) use ($user) {
                    // User-specific notifications
                    $q->where('user_id', $user->id)
                      ->orWhere('install_user_id', $user->id);
                    
                    // Also get broadcast notifications (is_multi = 1)
                    $q->orWhere('is_multi', 1);
                })
                ->orderBy('created_at', 'desc');
            
            if ($unreadOnly) {
                $query->where('is_read', 0);
            }
            
            $notifications = $query->paginate($perPage);
            
            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return $this->formatNotification($notification);
            });
            
            // Get unread count
            $unreadCount = Notification::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('install_user_id', $user->id)
                      ->orWhere('is_multi', 1);
                })
                ->where('is_read', 0)
                ->count();

            return $this->successResponse([
                'notifications' => $formattedNotifications,
                'unread_count' => $unreadCount,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ]
            ], 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get notifications failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve notifications.');
        }
    }

    /**
     * Get single notification details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $notification = Notification::where('id', $id)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('install_user_id', $user->id)
                      ->orWhere('is_multi', 1);
                })
                ->first();
            
            if (!$notification) {
                return $this->notFoundResponse('Notification not found.');
            }
            
            // Mark as read
            $notification->update(['is_read' => 1]);

            return $this->successResponse([
                'notification' => $this->formatNotification($notification)
            ], 'Notification retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get notification failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve notification.');
        }
    }

    /**
     * Mark notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $notification = Notification::where('id', $id)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('install_user_id', $user->id);
                })
                ->first();
            
            if (!$notification) {
                return $this->notFoundResponse('Notification not found.');
            }
            
            $notification->update(['is_read' => 1]);

            return $this->successResponse(null, 'Notification marked as read');
        } catch (\Exception $e) {
            Log::error('Mark notification as read failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to mark notification as read.');
        }
    }

    /**
     * Mark all notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            
            Notification::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('install_user_id', $user->id);
                })
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            return $this->successResponse(null, 'All notifications marked as read');
        } catch (\Exception $e) {
            Log::error('Mark all notifications as read failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to mark notifications as read.');
        }
    }

    /**
     * Get unread notification count.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request)
    {
        try {
            $user = $request->user();
            
            $count = Notification::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('install_user_id', $user->id)
                      ->orWhere('is_multi', 1);
                })
                ->where('is_read', 0)
                ->count();

            return $this->successResponse([
                'unread_count' => $count
            ], 'Unread count retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get unread count failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve unread count.');
        }
    }

    /**
     * Format notification data for API response.
     */
    protected function formatNotification($notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'is_read' => (bool) $notification->is_read,
            'is_broadcast' => (bool) $notification->is_multi,
            'publish_info' => $notification->publish_info,
            'created_at' => $notification->created_at ? $notification->created_at->toISOString() : null,
        ];
    }
}
