<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Buat notifikasi baru
     */
    public static function create($title, $message, $type = 'info', $data = [])
    {
        return Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data
        ]);
    }

    /**
     * Buat notifikasi untuk jadwal yang diedit
     */
    public static function scheduleUpdated($schedule)
    {
        return self::create(
            'Jadwal Diperbarui',
            "Jadwal {$schedule->day} ({$schedule->date->format('d-m-Y')}) berhasil diperbarui",
            'success',
            [
                'schedule_id' => $schedule->id,
                'day' => $schedule->day,
                'date' => $schedule->date->format('d-m-Y')
            ]
        );
    }

    /**
     * Buat notifikasi untuk jadwal yang dihapus
     */
    public static function scheduleDeleted($schedule)
    {
        return self::create(
            'Jadwal Dihapus',
            "Jadwal {$schedule->day} ({$schedule->date->format('d-m-Y')}) berhasil dihapus",
            'warning',
            [
                'day' => $schedule->day,
                'date' => $schedule->date->format('d-m-Y')
            ]
        );
    }

    /**
     * Buat notifikasi untuk jadwal yang ditambahkan
     */
    public static function scheduleCreated($schedule)
    {
        return self::create(
            'Jadwal Ditambahkan',
            "Jadwal {$schedule->day} ({$schedule->date->format('d-m-Y')}) berhasil ditambahkan",
            'success',
            [
                'schedule_id' => $schedule->id,
                'day' => $schedule->day,
                'date' => $schedule->date->format('d-m-Y')
            ]
        );
    }

    /**
     * Ambil semua notifikasi
     */
    public static function getAll($limit = 10)
    {
        return Notification::orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Ambil notifikasi yang belum dibaca
     */
    public static function getUnread($limit = 10)
    {
        return Notification::unread()->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Hitung jumlah notifikasi yang belum dibaca
     */
    public static function getUnreadCount()
    {
        return Notification::unread()->count();
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca
     */
    public static function markAsRead($id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        return $notification;
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca
     */
    public static function markAllAsRead()
    {
        return Notification::unread()->update(['read_at' => now()]);
    }
} 