<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // Contoh data: memakai salah satu nama dari gambar (misal: BUDI SANTOSO)
        Member::firstOrCreate(
            ['name' => 'BUDI SANTOSO'],
            [
                'rank' => 'KOMBES POL',
                'nrp' => '67120573',
                'position' => 'KABAG APLIKASI ROTEKINFO DIV TIK POLRI',
                'annual_quota' => 12,
            ]
        );
    }
}


