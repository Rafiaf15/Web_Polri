<?php

namespace App\Services;

use Carbon\Carbon;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class PdfScheduleService
{
    /**
     * Import jadwal dari PDF
     */
    public static function importFromPdf($file, $pdfPath = null, $pdfFilename = null)
    {
        $extractedData = self::debugExtractedData($file);
        $parsedSchedules = $extractedData['parsed_schedules'];

        $imported = 0;
        $merged = 0;
        $errors = [];
        $warnings = [];

        foreach ($parsedSchedules as $schedule) {
            // Check validation dari Python script
            if (isset($extractedData['validation']['warnings'])) {
                $warnings = array_merge($warnings, $extractedData['validation']['warnings']);
            }

            if (self::hasIncompleteData($schedule)) {
                $errors[] = [
                    'schedule' => $schedule,
                    'error' => 'Incomplete data',
                    'missing_fields' => self::getMissingFields($schedule)
                ];
                continue;
            }

            try {
                // Tambahkan informasi PDF
                $schedule['source'] = 'pdf';
                $schedule['pdf_filename'] = $pdfFilename ?: ($file ? $file->getClientOriginalName() : 'imported.pdf');
                $schedule['pdf_path'] = $pdfPath;
                
                // Check if schedule with same date already exists
                $existingSchedule = \App\Models\Schedule::findByDate($schedule['date']);
                
                if ($existingSchedule) {
                    // Merge activities with existing schedule
                    $existingSchedule->mergeActivities($schedule['activities']);
                    $merged++;
                } else {
                    // Create new schedule
                    \App\Models\Schedule::create($schedule);
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'schedule' => $schedule,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'total' => count($parsedSchedules),
            'imported' => $imported,
            'merged' => $merged,
            'errors' => $errors,
            'warnings' => $warnings,
            'extraction_info' => [
                'items_found' => $extractedData['items_found'] ?? 0,
                'extraction_success' => $extractedData['extraction_success'] ?? false,
                'text_length' => $extractedData['raw_text_length'] ?? 0
            ]
        ];
    }

    /**
     * Mengecek apakah data jadwal belum lengkap
     */
    public static function hasIncompleteData($schedule)
    {
        $requiredFields = ['day', 'date', 'time', 'room', 'activities'];
        
        foreach ($requiredFields as $field) {
            if (empty($schedule[$field])) {
                return true;
            }
        }

        // Khusus untuk activities, pastikan array tidak kosong
        if (is_array($schedule['activities']) && count($schedule['activities']) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Mendapatkan field yang hilang dari schedule
     */
    public static function getMissingFields($schedule)
    {
        $requiredFields = ['day', 'date', 'time', 'room', 'activities'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($schedule[$field])) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * Ekstrak data dari PDF untuk debug
     */
    public static function debugExtractedData($file)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($file->getRealPath());
        $text = $pdf->getText();

        // Parsing menggunakan script Python yang sudah diperbaiki
        $parsed = self::parseSchedules($text, $file);

        return [
            'raw_text' => $text,
            'parsed_schedules' => $parsed['schedules'] ?? [],
            'items_found' => $parsed['items_found'] ?? 0,
            'extraction_success' => $parsed['extraction_success'] ?? false,
            'raw_text_length' => $parsed['raw_text_length'] ?? 0,
            'validation' => $parsed['validation'] ?? [],
            'debug' => $parsed['debug'] ?? []
        ];
    }

    /**
     * Parsing jadwal dari teks PDF menggunakan script Python yang diperbaiki
     */
    private static function parseSchedules($text, $file = null)
    {
        $scriptPath = base_path('python/extract_schedule.py'); // script Python

        // Pastikan UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');

        if (!file_exists($scriptPath)) {
            Log::error("Python script not found: " . $scriptPath);
            return [
                'schedules' => [],
                'extraction_success' => false,
                'items_found' => 0
            ];
        }

        // Jalankan Python script via stdin (lebih aman dari file temp)
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];

        // Prefer 'python' on Windows; fallback to 'python3'
        $commands = [
            'python ' . escapeshellarg($scriptPath),
            'python3 ' . escapeshellarg($scriptPath)
        ];

        $process = null;
        $cmdUsed = null;
        foreach ($commands as $candidate) {
            $tmp = @proc_open($candidate, $descriptorspec, $pipes);
            if (is_resource($tmp)) {
                $process = $tmp;
                $cmdUsed = $candidate;
                break;
            }
        }

        if (!is_resource($process)) {
            Log::error("Failed to start Python process (tried: " . implode(', ', $commands) . ")");
            return [
                'schedules' => [],
                'extraction_success' => false,
                'items_found' => 0
            ];
        }

        // Kirim teks ke stdin
        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        // Baca output
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnValue = proc_close($process);

        if ($returnValue !== 0) {
            Log::error("Python script failed (cmd: {$cmdUsed}) with return code: $returnValue, errors: $errors");
            return [
                'schedules' => [],
                'extraction_success' => false,
                'items_found' => 0
            ];
        }

        if (!$output) {
            Log::error("Python script returned no output");
            return [
                'schedules' => [],
                'extraction_success' => false,
                'items_found' => 0
            ];
        }

        $data = json_decode($output, true);

        if (!$data) {
            Log::error("Failed to parse Python script output as JSON");
            return [
                'schedules' => [],
                'extraction_success' => false,
                'items_found' => 0
            ];
        }

        // Gunakan struktur baru dari Python script
        $schedules = [];
        
        if (isset($data['schedule_items']) && is_array($data['schedule_items'])) {
            // Format baru: gunakan schedule_items
            foreach ($data['schedule_items'] as $item) {
                $schedule = self::convertItemToSchedule($item, $file, null); // pdfPath akan di-set di importFromPdf
                if ($schedule) {
                    $schedules[] = $schedule;
                }
            }
        } else {
            // Fallback ke format lama untuk kompatibilitas
            $schedules = self::convertLegacyFormat($data, $file);
        }

        return [
            'schedules' => $schedules,
            'extraction_success' => $data['extraction_success'] ?? false,
            'items_found' => $data['items_found'] ?? count($schedules),
            'raw_text_length' => $data['raw_text_length'] ?? 0,
            'validation' => $data['validation'] ?? [],
            'debug' => $data['debug'] ?? []
        ];
    }

    /**
     * Konversi item dari format Python ke format Schedule model
     */
    private static function convertItemToSchedule($item, $pdfFile = null, $pdfPath = null)
    {
        $date = self::parseDate($item['date'] ?? '');
        $day = $item['day'] ?? '';
        $time = $item['time'] ?? '';
        $room = $item['location'] ?? '';
        $activity = $item['activity'] ?? 'Tidak ada keterangan kegiatan';

        // Skip jika data penting tidak ada
        if (!$date || !$time || !$room) {
            return null;
        }

        // Jika day kosong, coba deduce dari tanggal
        if (empty($day) && $date) {
            try {
                $carbonDate = Carbon::parse($date);
                $day = self::getDayNameInIndonesian($carbonDate->dayOfWeek);
            } catch (\Exception $e) {
                Log::warning("Could not parse date to get day: " . $e->getMessage());
            }
        }

        $scheduleData = [
            'day' => $day,
            'date' => $date,
            'time' => $time,
            'room' => $room,
            'activities' => [
                ['activity' => $activity, 'time' => $time]
            ],
            'source' => 'pdf',
            'pdf_filename' => $pdfFile ? self::getPdfFilename($pdfFile) : null,
            'pdf_path' => $pdfPath
        ];

        return $scheduleData;
    }

    /**
     * Mendapatkan nama file PDF
     */
    private static function getPdfFilename($file)
    {
        if ($file && method_exists($file, 'getClientOriginalName')) {
            return $file->getClientOriginalName();
        }
        return 'imported.pdf';
    }

    /**
     * Konversi format lama untuk backward compatibility
     */
    private static function convertLegacyFormat($data, $file = null)
    {
        $schedules = [];
        $maxCount = max(
            count($data['dates'] ?? []),
            count($data['times'] ?? []),
            count($data['locations'] ?? []),
            count($data['activities'] ?? []),
            count($data['days'] ?? [])
        );

        for ($i = 0; $i < $maxCount; $i++) {
            $day = $data['days'][$i] ?? $data['days'][0] ?? '';
            $date = isset($data['dates'][$i]) ? self::parseDate($data['dates'][$i]) : null;
            $time = $data['times'][$i] ?? $data['times'][0] ?? '';
            $room = $data['locations'][$i] ?? $data['locations'][0] ?? '';
            $activityText = $data['activities'][$i] ?? $data['activities'][0] ?? 'Tidak ada keterangan kegiatan';

            if ($date && $time && $room) {
                $schedules[] = [
                    'day' => $day,
                    'date' => $date,
                    'time' => $time,
                    'room' => $room,
                    'activities' => [
                        ['activity' => $activityText, 'time' => $time]
                    ],
                    'source' => 'pdf',
                    'pdf_filename' => $file ? self::getPdfFilename($file) : null
                ];
            }
        }

        return $schedules;
    }

    /**
     * Mengubah string tanggal menjadi format Y-m-d
     */
    private static function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            $dateStr = trim($dateStr);

            // Format DD/MM/YYYY
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateStr)) {
                return Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');
            }
            
            // Format DD-MM-YYYY
            if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $dateStr)) {
                return Carbon::createFromFormat('d-m-Y', $dateStr)->format('Y-m-d');
            }
            
            // Format Indonesia: "15 Januari 2024"
            if (preg_match('/^\d{1,2}\s+\w+\s+\d{4}$/', $dateStr)) {
                $monthMap = [
                    'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March',
                    'April' => 'April', 'Mei' => 'May', 'Juni' => 'June',
                    'Juli' => 'July', 'Agustus' => 'August', 'September' => 'September',
                    'Oktober' => 'October', 'November' => 'November', 'Desember' => 'December'
                ];
                
                foreach ($monthMap as $indo => $eng) {
                    $dateStr = str_replace($indo, $eng, $dateStr);
                }
            }
            
            // Fallback: gunakan Carbon parse
            return Carbon::parse($dateStr)->format('Y-m-d');
            
        } catch (\Exception $e) {
            Log::error("Parse date error for '$dateStr': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mendapatkan nama hari dalam bahasa Indonesia
     */
    private static function getDayNameInIndonesian($dayOfWeek)
    {
        $days = [
            0 => 'Minggu',
            1 => 'Senin', 
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];

        return $days[$dayOfWeek] ?? '';
    }

    /**
     * Uji regex secara manual (untuk debugging)
     */
    public static function debugRegexTest($text)
    {
        // Tetap mempertahankan fungsi debug regex lama untuk compatibility
        $patterns = [
            '/(?<day>[A-Za-z]+),\s*(?<date>\d{1,2} [A-Za-z]+ \d{4})\s*\|\s*(?<time>\d{2}:\d{2}-\d{2}:\d{2})\s*\|\s*(?<room>[^|]+)\|\s*(?<activity>.+)/',
            '/(?<date>\d{2}\/\d{2}\/\d{4})\s*\|\s*(?<time>\d{2}:\d{2}-\d{2}:\d{2})\s*\|\s*(?<room>[^|]+)\|\s*(?<activity>.+)/',
            '/(?<date>\d{2}\/\d{2}\/\d{4})\s*(?<time>\d{2}:\d{2}-\d{2}:\d{2})\s*(?<room>\S+)\s*(?<activity>.+)/'
        ];

        $results = [];

        foreach ($patterns as $index => $pattern) {
            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
            if (!empty($matches)) {
                $results[] = [
                    'pattern_index' => $index,
                    'matches' => $matches
                ];
            }
        }

        return [
            'text_length' => strlen($text),
            'text' => $text,
            'regex_results' => $results,
            'patterns_tested' => count($patterns),
            'recommendation' => 'Use Python script for better extraction results'
        ];
    }
}