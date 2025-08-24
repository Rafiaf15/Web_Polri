<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'day',
        'date', 
        'time',
        'room',
        'activities',
        'status',
        'source',
        'pdf_filename',
        'pdf_path'
    ];

    protected $casts = [
        'date' => 'date',
        'activities' => 'array'
    ];

    /**
     * Cek apakah ada konflik jadwal
     */
    public function checkForConflicts()
    {
        // Cek konflik internal (antar kegiatan dalam satu jadwal)
        if ($this->hasInternalConflicts()) {
            return true;
        }

        // Cek konflik dengan jadwal lain pada tanggal yang sama
        $otherSchedules = Schedule::where('id', '!=', $this->id)
            ->where('date', $this->date)
            ->get();

        foreach ($otherSchedules as $schedule) {
            if ($this->hasTimeConflict($schedule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek konflik antar kegiatan dalam satu jadwal
     */
    private function hasInternalConflicts()
    {
        if (empty($this->activities) || count($this->activities) <= 1) {
            return false;
        }

        $activityRanges = [];
        
        // Parse waktu untuk setiap kegiatan
        foreach ($this->activities as $activity) {
            // Gunakan waktu individual dari kegiatan, jika tidak ada gunakan waktu utama
            $activityTime = $activity['time'] ?? $this->time;
            $timeRange = $this->parseTimeRange($activityTime);
            if ($timeRange) {
                $activityRanges[] = [
                    'activity' => $activity['activity'],
                    'range' => $timeRange,
                    'time' => $activityTime
                ];
            }
        }

        // Cek overlap antar kegiatan
        for ($i = 0; $i < count($activityRanges); $i++) {
            for ($j = $i + 1; $j < count($activityRanges); $j++) {
                if ($this->timeRangesOverlap($activityRanges[$i]['range'], $activityRanges[$j]['range'])) {
                    // Debug: log konflik yang ditemukan
                    \Log::info("Konflik terdeteksi: {$activityRanges[$i]['activity']} ({$activityRanges[$i]['time']}) vs {$activityRanges[$j]['activity']} ({$activityRanges[$j]['time']})");
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cek konflik waktu antara dua jadwal
     */
    private function hasTimeConflict($otherSchedule)
    {
        $thisTime = $this->parseTimeRange($this->time);
        $otherTime = $this->parseTimeRange($otherSchedule->time);

        if ($thisTime && $otherTime) {
            return $this->timeRangesOverlap($thisTime, $otherTime);
        }

        return false;
    }

    /**
     * Parse range waktu (contoh: "08:00 - 10:00", "08.30 - selesai")
     */
    private function parseTimeRange($timeRange)
    {
        if (empty($timeRange)) return null;

        // Handle "selesai" format
        if (strpos($timeRange, 'selesai') !== false) {
            $startTime = preg_replace('/\s*-\s*selesai.*/i', '', $timeRange);
            $startTime = trim($startTime);
            
            // Convert to 24-hour format if needed
            $startTime = $this->normalizeTime($startTime);
            
            return [
                'start' => strtotime($startTime),
                'end' => strtotime('23:59') // End of day
            ];
        }

        // Handle normal time range
        $times = explode(' - ', $timeRange);
        if (count($times) === 2) {
            $startTime = $this->normalizeTime(trim($times[0]));
            $endTime = $this->normalizeTime(trim($times[1]));
            
            return [
                'start' => strtotime($startTime),
                'end' => strtotime($endTime)
            ];
        }

        // Single time
        $singleTime = $this->normalizeTime(trim($timeRange));
        return [
            'start' => strtotime($singleTime),
            'end' => strtotime($singleTime) + 3600 // 1 hour duration
        ];
    }

    /**
     * Normalize time format
     */
    private function normalizeTime($time)
    {
        // Remove WIB, etc.
        $time = preg_replace('/\s*WIB.*/i', '', $time);
        $time = trim($time);
        
        // Convert dot to colon
        $time = str_replace('.', ':', $time);
        
        // Ensure proper format
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time;
        }
        
        return $time;
    }

    /**
     * Cek apakah dua range waktu overlap
     */
    private function timeRangesOverlap($range1, $range2)
    {
        if (!$range1 || !$range2) return false;
        
        return $range1['start'] < $range2['end'] && $range2['start'] < $range1['end'];
    }

    /**
     * Update status berdasarkan konflik
     */
    public function updateConflictStatus()
    {
        $hasConflict = $this->checkForConflicts();
        $oldStatus = $this->status;
        $this->status = $hasConflict ? 'conflict' : 'available';
        
        // Hanya save jika status berubah
        if ($oldStatus !== $this->status) {
            $this->save();
        }
    }

    /**
     * Force update status konflik (untuk debugging)
     */
    public function forceUpdateConflictStatus()
    {
        $hasConflict = $this->checkForConflicts();
        $this->status = $hasConflict ? 'conflict' : 'available';
        $this->save();
        return $this->status;
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass()
    {
        return $this->status === 'available' ? 'badge bg-success' : 'badge bg-danger';
    }

    /**
     * Get status text
     */
    public function getStatusText()
    {
        return $this->status === 'available' ? 'Tidak Bentrok' : 'Bentrok';
    }

    /**
     * Cari jadwal yang sudah ada dengan tanggal yang sama
     */
    public static function findByDate($date)
    {
        return self::where('date', $date)->first();
    }

    /**
     * Gabungkan kegiatan ke jadwal yang sudah ada
     */
    public function mergeActivities($newActivities)
    {
        $existingActivities = $this->activities ?? [];
        $mergedActivities = array_merge($existingActivities, $newActivities);
        
        $this->update(['activities' => $mergedActivities]);
        $this->forceUpdateConflictStatus();
        
        return $this;
    }

    /**
     * Override update method untuk memastikan status tidak berubah secara tidak sengaja
     */
    public function update(array $attributes = [], array $options = [])
    {
        // Jika status tidak diupdate secara eksplisit, jangan ubah
        if (!isset($attributes['status'])) {
            unset($attributes['status']);
        }
        
        return parent::update($attributes, $options);
    }
} 