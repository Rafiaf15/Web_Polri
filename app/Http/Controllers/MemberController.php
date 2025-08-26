<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Cuti;
use Carbon\Carbon;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $year = (int)($request->get('year', Carbon::now()->year));

        // Handle reset-all via query parameter
        if ($request->get('reset') === 'all') {
            Cuti::whereYear('tanggal_mulai', $year)
                ->whereYear('tanggal_selesai', $year)
                ->delete();

            return redirect()->route('cuti.sisa', ['year' => $year])
                ->with('success', 'Pemakaian cuti semua anggota untuk tahun ' . $year . ' berhasil di-reset.');
        }

        $members = Member::orderBy('name')->get();

        $rows = $members->map(function(Member $m) use ($year) {
            return [
                'member' => $m,
                'jenis' => $m->getJenisCutiForYear($year),
                'used' => $m->getUsedLeaveDaysForYear($year),
                'remaining' => $m->getRemainingLeaveDaysForYear($year),
                'quota' => $m->annual_quota,
            ];
        });

        return view('cuti.sisa', [
            'rows' => $rows,
            'year' => $year
        ]);
    }
}



