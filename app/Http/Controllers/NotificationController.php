<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    /**
     * Tampilkan semua notifikasi
     */
    public function index()
    {
        $notifications = NotificationService::getAll(20);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Ambil notifikasi untuk AJAX
     */
    public function getNotifications()
    {
        $notifications = NotificationService::getUnread(5);
        $unreadCount = NotificationService::getUnreadCount();
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca
     */
    public function markAsRead($id)
    {
        NotificationService::markAsRead($id);
        return response()->json(['success' => true]);
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca
     */
    public function markAllAsRead()
    {
        NotificationService::markAllAsRead();
        return response()->json(['success' => true]);
    }
} 