<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data yang ada untuk menghindari duplikasi
        Schedule::truncate();

        // Jadwal Senin - Tidak bentrok
        Schedule::create([
            'day' => 'Senin',
            'date' => '2025-01-27',
            'time' => '08:00 - 16:00',
            'room' => 'Ruang Meeting Lt. 1',
            'activities' => [
                ['activity' => 'Rapat Divisi', 'time' => '08:00 - 09:00'],
                ['activity' => 'Pelatihan Sistem', 'time' => '09:00 - 12:00'],
                ['activity' => 'Maintenance Server', 'time' => '13:00 - 16:00']
            ],
            'status' => 'available'
        ]);

        // Jadwal Selasa - Tidak bentrok
        Schedule::create([
            'day' => 'Selasa',
            'date' => '2025-01-28',
            'time' => '08:00 - 16:00',
            'room' => 'Ruang Server Lt. 2',
            'activities' => [
                ['activity' => 'Update Software', 'time' => '08:00 - 10:00'],
                ['activity' => 'Backup Data', 'time' => '10:00 - 12:00'],
                ['activity' => 'Monitoring Jaringan', 'time' => '13:00 - 16:00']
            ],
            'status' => 'available'
        ]);

        // Jadwal Rabu - Bentrok (overlap waktu)
        Schedule::create([
            'day' => 'Rabu',
            'date' => '2025-01-29',
            'time' => '08:00 - 16:00',
            'room' => 'Ruang Training Lt. 3',
            'activities' => [
                ['activity' => 'Rapat Koordinasi', 'time' => '08:00 - 10:00'],
                ['activity' => 'Pelatihan Database', 'time' => '09:30 - 12:00'], // Bentrok dengan rapat
                ['activity' => 'Instalasi Hardware', 'time' => '13:00 - 16:00']
            ],
            'status' => 'conflict'
        ]);

        // Jadwal Kamis - Tidak bentrok
        Schedule::create([
            'day' => 'Kamis',
            'date' => '2025-01-30',
            'time' => '08:00 - 16:00',
            'room' => 'Ruang Audit Lt. 1',
            'activities' => [
                ['activity' => 'Audit Sistem', 'time' => '08:00 - 11:00'],
                ['activity' => 'Lunch Break', 'time' => '11:00 - 12:00'],
                ['activity' => 'Pengembangan Aplikasi', 'time' => '12:00 - 16:00']
            ],
            'status' => 'available'
        ]);

        // Jadwal Jumat - Bentrok (overlap waktu)
        Schedule::create([
            'day' => 'Jumat',
            'date' => '2025-01-31',
            'time' => '08:00 - 16:00',
            'room' => 'Ruang Meeting Lt. 2',
            'activities' => [
                ['activity' => 'Meeting Tim', 'time' => '08:00 - 09:00'],
                ['activity' => 'Testing Sistem', 'time' => '08:30 - 11:00'], // Bentrok dengan meeting
                ['activity' => 'Dokumentasi', 'time' => '11:00 - 12:00'],
                ['activity' => 'Evaluasi Mingguan', 'time' => '13:00 - 16:00']
            ],
            'status' => 'conflict'
        ]);
    }
} 