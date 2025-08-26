<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        Member::updateOrCreate(
            ['name' => 'Pancar Anugrah Sejati, S.KOM.'],
            [
                'rank' => 'IPDA',
                'nrp' => '9908174',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'DOBY PRAYADINATA, S.T., M.T. '],
            [
                'rank' => 'AKBP ',
                'nrp' => '80061251',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );
        
        Member::updateOrCreate(
            ['name' => 'KARTIKA WIDYASTUTI, S.Kom.'],
            [
                'rank' => 'Penata',
                'nrp' => '197902272008122001 ',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        // Tambahan sesuai daftar terbaru
        Member::updateOrCreate(
            ['name' => 'VALENTINO ALFA TATAREDA, S.H., S.I.K.'],
            [
                'rank' => 'BRIGJEN POL',
                'nrp' => '72070512',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'RIO MALIK WICAKSONO, S.T., M.M.'],
            [
                'rank' => 'BRIGPOL',
                'nrp' => '93101215',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'YULIZA UTAMI'],
            [
                'rank' => 'BRIPTU',
                'nrp' => '97070866',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'YOGI ARYA PRANATA'],
            [
                'rank' => 'BRIPDA',
                'nrp' => '01030342',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'MUHAMMAD RAFI SUGIANTO'],
            [
                'rank' => 'BRIPDA',
                'nrp' => '03071009',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'SARTIKA WIDYASTUTI, S.Kom.'],
            [
                'rank' => 'PENDA TK. I',
                'nrp' => '19790227 200812 2 001',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'JASMINE, S.H.'],
            [
                'rank' => 'PENGATUR',
                'nrp' => '19701210 201412 2 001',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'Ir. MOCH. SJAMSUL ARIEF, M.T.'],
            [
                'rank' => 'KOMBES POL',
                'nrp' => '68100410',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'DEWI RETNOWATI, A.Md.'],
            [
                'rank' => 'AKBP',
                'nrp' => '68030447',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'RULLY DWI HANDOKO, S.H.'],
            [
                'rank' => 'AIPTU',
                'nrp' => '82060586',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'UTIN DESI PURNAMASARI'],
            [
                'rank' => 'BRIPTU',
                'nrp' => '97120079',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'MUHAMMAD ANTHONY ALDRIANO, S.H.'],
            [
                'rank' => 'BRIPTU',
                'nrp' => '99110048',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'MUHAMMAD REZA FAHLEVI HARAHAP'],
            [
                'rank' => 'BRIPDA',
                'nrp' => '02080219',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'TATI KARYATI'],
            [
                'rank' => 'PENDA TK. I',
                'nrp' => '19770512 199903 2 001',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'NOOR CHILALLIAH'],
            [
                'rank' => 'PENDA TK. I',
                'nrp' => '19760630 200312 2 003',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'DENY CAHYANINGTYAS'],
            [
                'rank' => 'PENDA TK. I',
                'nrp' => '19761205 200312 1 004',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'FERRY FERDIAN, S.I.K.'],
            [
                'rank' => 'AKBP',
                'nrp' => '79020757',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'DHANI PRIJUTOMO, Amd.'],
            [
                'rank' => 'AIPDA',
                'nrp' => '82041489',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'M. RIYAN HAZLI HARKEMRI'],
            [
                'rank' => 'BRIPDA',
                'nrp' => '04080232',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'ERWIN FIRMANSYAH'],
            [
                'rank' => 'BRIPDA',
                'nrp' => '02120016',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'ROSITA ANANDIA TRILESTARI, S.M.'],
            [
                'rank' => 'BRIPTU',
                'nrp' => '97050792',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'DJUHARWATI, S.H.'],
            [
                'rank' => 'PENATA TK. I',
                'nrp' => '19720518 200312 2 003',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'I NYOMAN PRAYUDHI, A.Md.'],
            [
                'rank' => 'PENDA TK. I',
                'nrp' => '19870120 201101 1 001',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );

        Member::updateOrCreate(
            ['name' => 'MEYANDRI NUGRAHA, A.Md.'],
            [
                'rank' => 'PENDA TK. I',
                'nrp' => '19860518 200912 1 001',
                'jenis_cuti' => '-',
                'annual_quota' => 12,
            ]
        );
    }
}


