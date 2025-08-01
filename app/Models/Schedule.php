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
        'status'
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
        // Cek konflik dalam jadwal yang sama
        if ($this->hasInternalConflicts()) {
            return true;
        }

        // Cek konflik dengan jadwal lain
        $otherSchedules = Schedule::where('id', '!=', $this->id)
            ->where('day', $this->day)
            ->get();

        foreach ($otherSchedules as $schedule) {
            if ($this->hasTimeConflict($schedule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek konflik dalam jadwal yang sama
     */
    private function hasInternalConflicts()
    {
        $activities = $this->activities ?? [];
        
        for ($i = 0; $i < count($activities); $i++) {
            for ($j = $i + 1; $j < count($activities); $j++) {
                if (isset($activities[$i]['time']) && isset($activities[$j]['time'])) {
                    $time1 = $this->parseTimeRange($activities[$i]['time']);
                    $time2 = $this->parseTimeRange($activities[$j]['time']);
                    
                    if ($this->timeRangesOverlap($time1, $time2)) {
                        return true;
                    }
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
        $thisActivities = $this->activities ?? [];
        $otherActivities = $otherSchedule->activities ?? [];

        foreach ($thisActivities as $thisActivity) {
            foreach ($otherActivities as $otherActivity) {
                if (isset($thisActivity['time']) && isset($otherActivity['time'])) {
                    $thisTime = $this->parseTimeRange($thisActivity['time']);
                    $otherTime = $this->parseTimeRange($otherActivity['time']);

                    if ($this->timeRangesOverlap($thisTime, $otherTime)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Parse range waktu (contoh: "08:00 - 10:00")
     */
    private function parseTimeRange($timeRange)
    {
        $times = explode(' - ', $timeRange);
        if (count($times) === 2) {
            return [
                'start' => strtotime($times[0]),
                'end' => strtotime($times[1])
            ];
        }
        return null;
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