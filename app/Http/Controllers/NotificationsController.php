<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OneSignal;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Get only admin notifications if user is admin
            $query = Notification::where('id_user', $user->id_user);
            
            // Only get admin-relevant notification types for admin users
            if ($user->role === 'admin') {
                $query->whereIn('type', ['payment_verification', 'tenant_verification']);
            }
            
            $notifications = $query->orderBy('created_at', 'DESC')->get();
            
            return response()->json([
                'status' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting notifications: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to get notifications'
            ], 500);
        }
    }
    
    public function markAsRead($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            
            // Check if notification belongs to current user
            if ($notification->id_user != auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            $notification->status = 'read';
            $notification->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }
    
    public function savePlayerId(Request $request)
    {
        try {
            $request->validate([
                'player_id' => 'required|string'
            ]);
            
            $user = auth()->user();
            $user->onesignal_player_id = $request->player_id;
            $user->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Player ID saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving player ID: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to save player ID'
            ], 500);
        }
    }
    
    // This method will be called from other controllers
    public function sendNotification($userId, $title, $message, $type, $data = [])
    {
        try {
            // Save notification in database
            $notification = new Notification();
            $notification->id_user = $userId;
            $notification->title = $title;
            $notification->message = $message;
            $notification->type = $type;
            $notification->status = 'unread';
            $notification->data = $data;
            $notification->save();
            
            // Send push notification to user
            $user = User::find($userId);
            if ($user && $user->onesignal_player_id) {
                // Send to this specific user
                $playerIds = [$user->onesignal_player_id];
            } else if ($type === 'payment_verification' || $type === 'tenant_verification') {
                // Send to all admins if it's an admin notification
                $playerIds = User::where('role', 'admin')
                    ->whereNotNull('onesignal_player_id')
                    ->pluck('onesignal_player_id')
                    ->toArray();
            } else {
                $playerIds = [];
            }
            
            if (!empty($playerIds)) {
                OneSignal::sendNotificationToUser(
                    $message,
                    $playerIds,
                    $url = null,
                    $data = array_merge($data, [
                        'type' => $type,
                        'id' => $notification->id_notification
                    ]),
                    $buttons = null,
                    $schedule = null,
                    $headings = $title
                );
            }
            
            return $notification;
        } catch (\Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());
            return null;
        }
    }
}
