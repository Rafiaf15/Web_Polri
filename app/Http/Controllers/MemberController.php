<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use Carbon\Carbon;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $year = (int)($request->get('year', Carbon::now()->year));
        $members = Member::orderBy('name')->get();

        $rows = $members->map(function(Member $m) use ($year) {
            return [
                'member' => $m,
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


