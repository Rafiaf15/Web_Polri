<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;

class DashboardController extends Controller
{
    public function index()
    {
        // Ambil data jadwal dari database sebagai objek model
        $schedules = Schedule::all();

        return view('dashboard.dashboard', compact('schedules'));
    }
} 