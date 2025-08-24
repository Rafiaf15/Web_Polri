<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        Member::firstOrCreate(
            ['name' => 'Pancar Anugrah Sejati, S.KOM.'],
            [
                'rank' => 'IPDA',
                'nrp' => '9908174',
                'position' => 'PS. PAMIN SUBBAGMONEV BAGJEMEN TIK DIV TIK POLRI',
                'annual_quota' => 12,
            ]
        );

        Member::firstOrCreate(
            ['name' => 'DOBY PRAYADINATA, S.T., M.T. '],
            [
                'rank' => 'AKBP ',
                'nrp' => '80061251',
                'position' => 'KASUBBAGKAMDATIN BAGKELOLAKAMDATIN RODATIN DIV TIK POLRI',
                'annual_quota' => 12,
            ]
        );
        
        Member::firstOrCreate(
            ['name' => 'KARTIKA WIDYASTUTI, S.Kom.'],
            [
                'rank' => 'Penata',
                'nrp' => '197902272008122001 ',
                'position' => 'PS. KAURTU RODATIN DIV TIK POLRI',
                'annual_quota' => 12,
            ]
        );
    }
}


