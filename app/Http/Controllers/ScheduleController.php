<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Services\NotificationService;
use App\Services\PdfScheduleService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
        try {
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
            
            // Buat notifikasi untuk semua user
            NotificationService::create(
                'Jadwal Baru Ditambahkan',
                "Jadwal {$schedule->day} ({$schedule->date->format('d-m-Y')}) berhasil ditambahkan oleh " . Auth::user()->name,
                'success',
                ['schedule_id' => $schedule->id]
            );

            return redirect()->route('schedule.index')->with('success', 'Jadwal berhasil ditambahkan!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Notifikasi untuk validasi gagal
            NotificationService::create(
                'Gagal Menambahkan Jadwal',
                'Validasi data jadwal gagal: ' . collect($e->errors())->flatten()->first(),
                'error'
            );
            return redirect()->back()->with('error', 'Gagal menambahkan jadwal: Data tidak valid')->withErrors($e->errors());
        } catch (\Exception $e) {
            // Notifikasi untuk error umum
            NotificationService::create(
                'Gagal Menambahkan Jadwal',
                'Terjadi kesalahan saat menambahkan jadwal: ' . $e->getMessage(),
                'error'
            );
            return redirect()->back()->with('error', 'Gagal menambahkan jadwal: ' . $e->getMessage());
        }
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
        
        // Buat notifikasi untuk semua user
        NotificationService::create(
            'Jadwal Diperbarui',
            "Jadwal {$schedule->day} ({$schedule->date->format('d-m-Y')}) berhasil diperbarui oleh " . Auth::user()->name,
            'info',
            ['schedule_id' => $schedule->id]
        );

        $statusMessage = $newStatus === 'conflict' ? ' (Status: Bentrok)' : ' (Status: Tidak Bentrok)';
        return redirect()->route('schedule.index')->with('success', 'Jadwal berhasil diperbarui!' . $statusMessage);
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        
        // Update status jadwal lain yang mungkin terpengaruh
        $this->updateRelatedSchedules($schedule);
        
        // Buat notifikasi untuk semua user
        NotificationService::create(
            'Jadwal Dihapus',
            "Jadwal {$schedule->day} ({$schedule->date->format('d-m-Y')}) berhasil dihapus oleh " . Auth::user()->name,
            'warning',
            ['schedule_id' => $schedule->id]
        );

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

    /**
     * Tampilkan halaman import PDF
     */
    public function showImportPdf()
    {
        return view('schedule.import-pdf');
    }

    /**
     * Import jadwal dari file PDF
     */
    public function importFromPdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240' // Max 10MB
        ]);

        try {
            $result = PdfScheduleService::importFromPdf($request->file('pdf_file'));
            
            $message = "Berhasil mengimpor {$result['imported']} jadwal dari {$result['total']} data yang ditemukan.";
            
            if (!empty($result['errors'])) {
                $message .= " Terdapat " . count($result['errors']) . " error dalam proses import.";
            }

            return redirect()->route('schedule.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimpor PDF: ' . $e->getMessage());
        }
    }

    /**
     * Preview data dari PDF sebelum import
     */
    public function previewPdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240'
        ]);

        try {
            // Debug: lihat data yang diekstrak
            $debugData = PdfScheduleService::debugExtractedData($request->file('pdf_file'));
            $schedules = $debugData['parsed_schedules'];
            
            return response()->json([
                'success' => true,
                'schedules' => $schedules,
                'count' => count($schedules),
                'debug' => $debugData // Untuk debugging
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Debug regex secara manual
     */
    public function debugRegex(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240'
        ]);

        try {
            // Ekstrak teks dari PDF
            $tempPath = $request->file('pdf_file')->store('temp', 'local');
            $fullPath = Storage::disk('local')->path($tempPath);
            
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();
            
            // Hapus file sementara
            Storage::disk('local')->delete($tempPath);
            
            // Test regex
            $debugResult = PdfScheduleService::debugRegexTest($text);
            
            return response()->json([
                'success' => true,
                'debug' => $debugResult
            ]);
        } catch (\Exception $e) {
            \Log::error('Debug regex error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
} 