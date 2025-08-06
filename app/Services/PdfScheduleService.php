<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Schedule;
use Carbon\Carbon;

class PdfScheduleService
{
    /**
     * Ekstrak jadwal dari file PDF
     */
    public static function extractScheduleFromPdf(UploadedFile $pdfFile)
    {
        // Simpan file PDF sementara
        $tempPath = $pdfFile->store('temp', 'local');
        $fullPath = Storage::disk('local')->path($tempPath);
        
        try {
            // Ekstrak teks dari PDF
            $text = self::extractTextFromPdf($fullPath);
            
            // Parse jadwal dari teks
            $schedules = self::parseScheduleFromText($text);
            
            // Hapus file sementara
            Storage::disk('local')->delete($tempPath);
            
            return $schedules;
        } catch (\Exception $e) {
            // Hapus file sementara jika terjadi error
            Storage::disk('local')->delete($tempPath);
            throw $e;
        }
    }
    
    /**
     * Ekstrak teks dari file PDF
     */
    private static function extractTextFromPdf($pdfPath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            // Debug: log teks yang diekstrak
            \Log::info('PDF Text extracted:', ['text' => $text]);
            
            // Jika teks kosong, throw exception
            if (empty(trim($text))) {
                throw new \Exception('Tidak dapat mengekstrak teks dari PDF. File mungkin kosong atau tidak dapat dibaca.');
            }
            
            return $text;
        } catch (\Exception $e) {
            \Log::error('PDF extraction error:', ['error' => $e->getMessage()]);
            throw new \Exception('Gagal mengekstrak teks dari PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Sample data untuk demo
     */
    private static function getSamplePdfText()
    {
        return "Senin, 15 Jan 2024 | 08:00-10:00 | Ruang A | Rapat Koordinasi
Selasa, 16 Jan 2024 | 14:00-16:00 | Ruang B | Meeting Tim
Rabu, 17 Jan 2024 | 09:00-11:00 | Ruang C | Presentasi Proyek
Kamis, 18 Jan 2024 | 13:00-15:00 | Ruang A | Training
Jumat, 19 Jan 2024 | 10:00-12:00 | Ruang B | Rapat Evaluasi";
    }
    
    /**
     * Debug method untuk melihat data yang diparse
     */
    public static function debugExtractedData(UploadedFile $pdfFile)
    {
        // Simpan file PDF sementara
        $tempPath = $pdfFile->store('temp', 'local');
        $fullPath = Storage::disk('local')->path($tempPath);
        
        try {
            // Ekstrak teks dari PDF
            $text = self::extractTextFromPdf($fullPath);
            
            // Parse jadwal dari teks
            $schedules = self::parseScheduleFromText($text);
            
            // Hapus file sementara
            Storage::disk('local')->delete($tempPath);
            
            return [
                'raw_text' => $text,
                'parsed_schedules' => $schedules,
                'count' => count($schedules)
            ];
        } catch (\Exception $e) {
            // Hapus file sementara jika terjadi error
            Storage::disk('local')->delete($tempPath);
            throw $e;
        }
    }
    
    /**
     * Parse jadwal dari teks yang diekstrak
     */
    private static function parseScheduleFromText($text)
    {
        try {
            $schedules = [];
            $lines = explode("\n", $text);
            // Proses per baris
            foreach ($lines as $line) {
                try {
                    $schedule = self::parseLine($line);
                    if ($schedule) {
                        $schedules[] = $schedule;
                    }
                } catch (\Exception $e) {
                    \Log::error('Error parsing line: ' . $e->getMessage());
                    continue;
                }
            }
            // Jika tidak ada yang ditemukan, coba regex pada seluruh teks
            if (count($schedules) === 0) {
                $patterns = [
                    // Format: Surat Undangan OSIS (lebih fleksibel)
                    '/Hari\s*\/\s*Tanggal\s*:?\s*(\w+),\s*(\d{1,2}\s+\w+\s+\d{4})\s*Waktu\s*:?\s*(?:Pukul\s*)?([0-9.]+)\s*WIB.*?Tempat\s*:?\s*([^\n]+)/i',
                    // Tambahan pola longgar untuk menangani variasi teks
                    '/Hari.*?Tanggal\s*:?\s*(\w+),\s*(\d{1,2}\s+\w+\s+\d{4}).*?Waktu\s*:?\s*(?:Pukul\s*)?([0-9.]+).*?Tempat\s*:?\s*([^\n]+)/i',
                    '/(\w+),\s*(\d{1,2}\s+\w+\s+\d{4}).*?([0-9.]+).*?([^\n]+)/i',
                    // Pola fallback yang sangat longgar
                    '/(\w+),?\s*(\d{1,2}\s+\w+\s+\d{4})?.*?([0-9]{1,2}[:.]?[0-9]{2}-[0-9]{1,2}[:.]?[0-9]{2}).*?([A-Za-z0-9\s]+)/i',
                    // Pola baru untuk format surat undangan dengan teks bebas
                    '/Hari\/Tanggal\s*:\s*(\w+),\s*(\d{1,2}\s+\w+\s+\d{4}).*?Waktu\s*:\s*Pukul\s*([0-9.]+)\s*WIB.*?Tempat\s*:\s*([^\n]+)/i',
                ];
                foreach ($patterns as $pattern) {
                    try {
                        if (preg_match($pattern, $text, $matches)) {
                            $day = isset($matches[1]) ? trim($matches[1]) : 'N/A';
                            $date = isset($matches[2]) ? self::parseDate($matches[2]) : null;
                            $time = isset($matches[3]) ? str_replace('.', ':', trim($matches[3])) . ':00' : null;
                            $room = isset($matches[4]) ? trim($matches[4]) : 'N/A';
                            $activity = 'Undangan Rapat/Acara';
                            $schedules[] = [
                                'day' => $day,
                                'date' => $date,
                                'time' => $time,
                                'room' => $room,
                                'activities' => [
                                    [
                                        'activity' => $activity,
                                        'time' => $time
                                    ]
                                ]
                            ];
                        }
                    } catch (\Exception $e) {
                        \Log::error('Regex pattern error: ' . $e->getMessage());
                        continue;
                    }
                }
                // Jika masih kosong, coba parse dengan metode khusus untuk surat undangan
                if (count($schedules) === 0) {
                    $customSchedule = self::parseScheduleFromLetterText($text);
                    if ($customSchedule) {
                        $schedules[] = $customSchedule;
                    }
                }
            }
            return $schedules;
        } catch (\Exception $e) {
            \Log::error('Error parsing schedule from text: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Parse satu baris teks menjadi jadwal
     */
    private static function parseLine($line)
    {
        try {
            // Bersihkan line
            $line = trim($line);
            if (empty($line)) {
                return null;
            }
            
            // Pattern untuk mencocokkan format jadwal
            // Contoh format: "Senin, 15 Jan 2024 | 08:00-10:00 | Ruang A | Rapat Koordinasi"
            $patterns = [
                // Format: Hari, Tanggal | Waktu | Ruang | Kegiatan
                '/(\w+),\s*(\d{1,2}\s+\w+\s+\d{4})\s*\|\s*(\d{2}:\d{2}-\d{2}:\d{2})\s*\|\s*([^|]+)\s*\|\s*(.+)/',
                
                // Format: Tanggal | Waktu | Ruang | Kegiatan
                '/(\d{1,2}\/\d{1,2}\/\d{4})\s*\|\s*(\d{2}:\d{2}-\d{2}:\d{2})\s*\|\s*([^|]+)\s*\|\s*(.+)/',
                
                // Format: Tanggal | Waktu | Ruang | Kegiatan (tanpa separator)
                '/(\d{1,2}\/\d{1,2}\/\d{4})\s+(\d{2}:\d{2}-\d{2}:\d{2})\s+([^\s]+)\s+(.+)/',
                // Format: Surat Undangan OSIS
                '/Hari\/Tanggal\s*:\s*(\w+),\s*(\d{1,2}\s+\w+\s+\d{4})\s*Waktu\s*:\s*Pukul\s*([0-9.]+)\s*WIB.*Tempat\s*:\s*([^\n]+)/i',
                // Tambahan pola longgar untuk menangani variasi teks
                '/Hari.*?Tanggal\s*:\s*(\w+),\s*(\d{1,2}\s+\w+\s+\d{4}).*?Waktu\s*:\s*Pukul\s*([0-9.]+).*?Tempat\s*:\s*([^\n]+)/i',
                '/(\w+),\s*(\d{1,2}\s+\w+\s+\d{4}).*?([0-9.]+).*?([^\n]+)/i',
                // Pola fallback yang sangat longgar
                '/(\w+),?\s*(\d{1,2}\s+\w+\s+\d{4})?.*?([0-9]{1,2}[:.]?[0-9]{2}-[0-9]{1,2}[:.]?[0-9]{2}).*?([A-Za-z0-9\s]+)/i',
            ];
            
            foreach ($patterns as $pattern) {
                try {
                    if (preg_match($pattern, $line, $matches)) {
                        // Pola undangan OSIS
                        if (strpos($pattern, 'Hari/Tanggal') !== false) {
                            $day = trim($matches[1]);
                            $date = self::parseDate($matches[2]);
                            $time = trim($matches[3]) . ':00'; // Ubah 15.00 jadi 15:00:00
                            $room = trim($matches[4]);
                            $activity = 'Undangan Rapat/Acara';
                            return [
                                'day' => $day,
                                'date' => $date,
                                'time' => $time,
                                'room' => $room,
                                'activities' => [
                                    [
                                        'activity' => $activity,
                                        'time' => $time
                                    ]
                                ]
                            ];
                        }
                        $schedule = self::createScheduleFromMatches($matches, $pattern);
                        if ($schedule) {
                            return $schedule;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Regex pattern error: ' . $e->getMessage());
                    continue;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            // Jika terjadi error, return null
            \Log::error('Error parsing line: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buat objek jadwal dari hasil regex
     */
    private static function createScheduleFromMatches($matches, $pattern)
    {
        try {
            // Pattern 1: Hari, Tanggal | Waktu | Ruang | Kegiatan
            if (strpos($pattern, '(\w+),\s*(\d{1,2}\s+\w+\s+\d{4})') !== false) {
                $day = trim($matches[1]);
                $date = self::parseDate($matches[2]);
                $time = trim($matches[3]);
                $room = trim($matches[4]);
                $activity = trim($matches[5]);
                
                return [
                    'day' => $day,
                    'date' => $date,
                    'time' => $time,
                    'room' => $room,
                    'activities' => [
                        [
                            'activity' => $activity,
                            'time' => $time
                        ]
                    ]
                ];
            }
            
            // Pattern 2 & 3: Tanggal | Waktu | Ruang | Kegiatan
            $date = self::parseDate($matches[1]);
            $time = trim($matches[2]);
            $room = trim($matches[3]);
            $activity = trim($matches[4]);
            
            // Tentukan hari berdasarkan tanggal
            $day = self::getDayFromDate($date);
            
            return [
                'day' => $day,
                'date' => $date,
                'time' => $time,
                'room' => $room,
                'activities' => [
                    [
                        'activity' => $activity,
                        'time' => $time
                    ]
                ]
            ];
        } catch (\Exception $e) {
            // Jika terjadi error, return null
            \Log::error('Error creating schedule from matches: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parse tanggal dari berbagai format
     */
    private static function parseDate($dateString)
    {
        // Bersihkan string tanggal
        $dateString = trim($dateString);
        
        // Coba berbagai format tanggal
        $formats = [
            'd/m/Y',
            'd-m-Y',
            'd M Y',
            'd F Y',
            'Y-m-d',
        ];
        
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date && $date->year > 1900 && $date->year < 2100) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Lanjut ke format berikutnya
                continue;
            }
        }
        
        // Jika tidak ada format yang cocok, coba parse dengan Carbon::parse
        try {
            $date = Carbon::parse($dateString);
            if ($date && $date->year > 1900 && $date->year < 2100) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Jika semua gagal, return tanggal hari ini
            return Carbon::now()->format('Y-m-d');
        }
        
        // Fallback ke tanggal hari ini
        return Carbon::now()->format('Y-m-d');
    }
    
    /**
     * Dapatkan nama hari dari tanggal
     */
    private static function getDayFromDate($date)
    {
        try {
            $carbon = Carbon::parse($date);
            $days = [
                1 => 'Senin',
                2 => 'Selasa', 
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                0 => 'Minggu'
            ];
            
            return $days[$carbon->dayOfWeek] ?? 'Senin';
        } catch (\Exception $e) {
            // Jika gagal parse tanggal, return hari ini
            $today = Carbon::now();
            $days = [
                1 => 'Senin',
                2 => 'Selasa', 
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                0 => 'Minggu'
            ];
            
            return $days[$today->dayOfWeek];
        }
    }
    
    /**
     * Import jadwal dari PDF ke database
     */
    public static function importFromPdf(UploadedFile $pdfFile)
    {
        $schedules = self::extractScheduleFromPdf($pdfFile);
        $imported = 0;
        $errors = [];
        
        foreach ($schedules as $scheduleData) {
            try {
                // Cek apakah jadwal sudah ada
                $existing = Schedule::where('date', $scheduleData['date'])
                    ->where('room', $scheduleData['room'])
                    ->where('time', $scheduleData['time'])
                    ->first();
                
                if (!$existing) {
                    Schedule::create($scheduleData);
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Error importing schedule: " . $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($schedules)
        ];
    }

    /**
     * Debug method untuk menguji regex secara manual
     */
    public static function debugRegexTest($text)
    {
        $patterns = [
            // Format: Surat Undangan OSIS (lebih fleksibel)
            '/Hari\s*\/\s*Tanggal\s*:?\s*(\w+),\s*(\d{1,2}\s+\w+\s+\d{4})\s*Waktu\s*:?\s*(?:Pukul\s*)?([0-9.]+)\s*WIB.*?Tempat\s*:?\s*([^\n]+)/i',
            // Pattern yang lebih longgar
            '/Hari.*?Tanggal\s*:?\s*(\w+),\s*(\d{1,2}\s+\w+\s+\d{4}).*?Waktu\s*:?\s*(?:Pukul\s*)?([0-9.]+).*?Tempat\s*:?\s*([^\n]+)/i',
            // Pattern yang sangat longgar
            '/(\w+),\s*(\d{1,2}\s+\w+\s+\d{4}).*?([0-9.]+).*?([^\n]+)/i',
            // Pola fallback yang sangat longgar
            '/(\w+),?\s*(\d{1,2}\s+\w+\s+\d{4})?.*?([0-9]{1,2}[:.]?[0-9]{2}-[0-9]{1,2}[:.]?[0-9]{2}).*?([A-Za-z0-9\s]+)/i',
        ];
        
        $results = [];
        foreach ($patterns as $index => $pattern) {
            try {
                if (preg_match($pattern, $text, $matches)) {
                    $results[] = [
                        'pattern_index' => $index,
                        'matches' => $matches,
                        'pattern' => $pattern
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Regex debug pattern error: ' . $e->getMessage());
                continue;
            }
        }
        
        return [
            'text' => $text,
            'text_length' => strlen($text),
            'regex_results' => $results,
            'patterns_tested' => count($patterns)
        ];
    }
}
