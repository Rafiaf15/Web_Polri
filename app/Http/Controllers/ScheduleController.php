<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Services\NotificationService;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::all();
        return view('schedule.index', compact('schedules'));
    }

    public function piket()
    {
        $schedules = Schedule::all();
        return view('schedule.piket', compact('schedules'));
    }

    public function create()
    {
        return view('schedule.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'day' => 'required|string',
            'date' => 'required|date',
            'time' => 'required|string',
            'room' => 'required|string',
            'activities' => 'required|array',
            'activities.*.activity' => 'required|string',
            'activities.*.time' => 'required|string'
        ]);

        $schedule = Schedule::create($validatedData);
        
        // Cek dan update status konflik
        $schedule->forceUpdateConflictStatus();
        
        // Buat notifikasi
        NotificationService::scheduleCreated($schedule);

        return redirect()->route('schedule.index')->with('success', 'Jadwal berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $schedule = Schedule::findOrFail($id);
        return view('schedule.edit', compact('schedule'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'day' => 'required|string',
            'date' => 'required|date',
            'time' => 'required|string',
            'room' => 'required|string',
            'activities' => 'required|array',
            'activities.*.activity' => 'required|string',
            'activities.*.time' => 'required|string'
        ]);

        $schedule = Schedule::findOrFail($id);
        
        // Update data jadwal
        $schedule->update($validatedData);
        
        // Refresh model untuk mendapatkan data terbaru
        $schedule->refresh();
        
        // Cek dan update status konflik
        $newStatus = $schedule->forceUpdateConflictStatus();
        
        // Update status jadwal lain yang mungkin terpengaruh
        $this->updateRelatedSchedules($schedule);
        
        // Buat notifikasi
        NotificationService::scheduleUpdated($schedule);

        $statusMessage = $newStatus === 'conflict' ? ' (Status: Bentrok)' : ' (Status: Tidak Bentrok)';
        return redirect()->route('schedule.index')->with('success', 'Jadwal berhasil diperbarui!' . $statusMessage);
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        
        // Update status jadwal lain yang mungkin terpengaruh
        $this->updateRelatedSchedules($schedule);
        
        // Buat notifikasi
        NotificationService::scheduleDeleted($schedule);

        return redirect()->route('schedule.index')->with('success', 'Jadwal berhasil dihapus!');
    }

    /**
     * Update status jadwal lain yang mungkin terpengaruh
     */
    private function updateRelatedSchedules($currentSchedule)
    {
        $relatedSchedules = Schedule::where('day', $currentSchedule->day)
            ->where('id', '!=', $currentSchedule->id)
            ->get();

        foreach ($relatedSchedules as $schedule) {
            $schedule->forceUpdateConflictStatus();
        }
    }

    /**
     * Debug method untuk mengecek konflik
     */
    public function debugConflicts()
    {
        $schedules = Schedule::all();
        $conflicts = [];
        
        foreach ($schedules as $schedule) {
            $hasConflict = $schedule->checkForConflicts();
            $conflicts[] = [
                'id' => $schedule->id,
                'day' => $schedule->day,
                'status' => $schedule->status,
                'has_conflict' => $hasConflict,
                'activities' => $schedule->activities
            ];
        }
        
        return response()->json($conflicts);
    }
} 