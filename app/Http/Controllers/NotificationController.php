<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Get unread notification count for navbar indicator
     */
    public function getUnreadCount()
    {
        $count = Notification::unreadCountForUser(Auth::id());
        
        return response()->json([
            'status' => 'success',
            'unread_count' => $count
        ]);
    }

    /**
     * Get notifications for dropdown
     */
    public function getNotifications(Request $request)
    {
        $currentUser = Auth::user();
        $limit = $request->get('limit', 10);
        
        $notifications = Notification::where('user_id', $currentUser->id)
                                   ->with('fromUser:id,full_name,profile_picture,prodi')
                                   ->orderBy('created_at', 'desc')
                                   ->limit($limit)
                                   ->get();

        $unreadCount = Notification::unreadCountForUser($currentUser->id);

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'from_user' => $notification->fromUser ? [
                        'id' => $notification->fromUser->id,
                        'name' => $notification->fromUser->full_name,
                        'profile_picture' => $notification->fromUser->profile_picture,
                        'prodi' => $notification->fromUser->prodi
                    ] : null
                ];
            }),
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id'
        ]);

        $notification = Notification::where('id', $request->notification_id)
                                  ->where('user_id', Auth::id())
                                  ->first();

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
                   ->where('is_read', false)
                   ->update([
                       'is_read' => true,
                       'read_at' => now()
                   ]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Handle like back action from notification
     */
    public function likeBack(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
            'from_user_id' => 'required|integer|exists:users,id'
        ]);

        $currentUser = Auth::user();
        $notification = Notification::where('id', $request->notification_id)
                                  ->where('user_id', $currentUser->id)
                                  ->where('type', 'like_received')
                                  ->first();

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found or invalid'
            ], 404);
        }

        // Use ProfileController's storeInteraction logic
        $profileController = new \App\Http\Controllers\ProfileController();
        $likeRequest = new Request([
            'target_user_id' => $request->from_user_id,
            'action_type' => 'like'
        ]);

        // Call the existing interaction logic
        $response = $profileController->storeInteraction($likeRequest);
        $responseData = json_decode($response->getContent(), true);

        // Mark notification as read after successful like back
        if ($responseData['status'] === 'success') {
            $notification->markAsRead();
        }

        return $response;
    }

    /**
     * Show notifications page (optional - for full page view)
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
                                   ->with('fromUser')
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(20);

        // Mark all as read when viewing the page
        Notification::where('user_id', Auth::id())
                   ->where('is_read', false)
                   ->update(['is_read' => true, 'read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }
}