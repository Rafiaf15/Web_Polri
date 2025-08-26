<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'rank', 'nrp', 'jenis_cuti', 'annual_quota'
    ];

    /**
     * Hitung pemakaian cuti tahun berjalan (dalam hari)
     */
    public function getUsedLeaveDaysForYear(int $year): int
    {
        return Cuti::where('nama', $this->name)
            ->whereYear('tanggal_mulai', $year)
            ->whereYear('tanggal_selesai', $year)
            ->sum(\DB::raw('DATEDIFF(tanggal_selesai, tanggal_mulai) + 1'));
    }

    /**
     * Sisa cuti tahun berjalan
     */
    public function getRemainingLeaveDaysForYear(int $year): int
    {
        $used = $this->getUsedLeaveDaysForYear($year);
        $remaining = max(0, (int)$this->annual_quota - (int)$used);
        return $remaining;
    }

    /**
     * Jenis cuti yang berlaku/terakhir untuk tahun tertentu (fallback: tahunan)
     */
    public function getJenisCutiForYear(int $year): string
    {
        $latest = Cuti::where('nama', $this->name)
            ->whereYear('tanggal_mulai', $year)
            ->orderBy('tanggal_mulai', 'desc')
            ->first();

        return $latest->jenis_cuti ?? ($this->jenis_cuti ?? 'tahunan');
    }
}


