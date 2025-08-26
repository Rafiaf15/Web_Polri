<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cuti;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use Smalot\PdfParser\Parser;

class CutiController extends Controller
{
    /**
     * Tampilkan daftar cuti
     */
    public function index()
    {
        // Tampilkan sisa jatah cuti per anggota, bukan form pengajuan
        $year = (int) now()->year;
        $members = Member::orderBy('name')->get();

        $rows = $members->map(function (Member $m) use ($year) {
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

    /**
     * Tampilkan form pengajuan cuti
     */
    public function create()
    {
        return view('cuti.create');
    }

    /**
     * Simpan pengajuan cuti baru
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'biro' => 'required|string|max:255',
            'jenis_cuti' => 'required|in:tahunan,sakit,melahirkan,penting,lainnya',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:10'
        ]);

        // Hitung jumlah hari cuti
        $tanggalMulai = \Carbon\Carbon::parse($validatedData['tanggal_mulai']);
        $tanggalSelesai = \Carbon\Carbon::parse($validatedData['tanggal_selesai']);
        $jumlahHari = $tanggalMulai->diffInDays($tanggalSelesai) + 1; // +1 karena termasuk hari pertama

        // Validasi maksimal 12 hari
        if ($jumlahHari > 12) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['tanggal_selesai' => 'Maksimal cuti adalah 12 hari. Anda mengajukan ' . $jumlahHari . ' hari.']);
        }

        $cuti = Cuti::create([
            'user_id' => Auth::id(),
            'nama' => $validatedData['nama'],
            'biro' => $validatedData['biro'],
            'jenis_cuti' => $validatedData['jenis_cuti'],
            'tanggal_mulai' => $validatedData['tanggal_mulai'],
            'tanggal_selesai' => $validatedData['tanggal_selesai'],
            'alasan' => $validatedData['alasan'],
            'status' => 'pending'
        ]);

        // Buat notifikasi untuk admin
        NotificationService::create(
            'Pengajuan Cuti Baru',
            $validatedData['nama'] . ' mengajukan cuti ' . $this->getJenisCutiText($validatedData['jenis_cuti']) . ' dari ' . $validatedData['tanggal_mulai'] . ' sampai ' . $validatedData['tanggal_selesai'],
            'info',
            ['cuti_id' => $cuti->id]
        );

        return redirect()->route('cuti.index')->with('success', 'Pengajuan cuti berhasil dikirim!');
    }

    /**
     * Tampilkan detail cuti
     */
    public function show($id)
    {
        $cuti = Cuti::with(['user', 'approver'])->findOrFail($id);
        
        // Cek apakah user berhak melihat cuti ini
        if (Auth::user()->role !== 'administrator' && $cuti->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('cuti.show', compact('cuti'));
    }

    /**
     * Tampilkan form edit cuti
     */
    public function edit($id)
    {
        $cuti = Cuti::findOrFail($id);
        
        // Hanya bisa edit jika masih pending dan milik sendiri
        if ($cuti->status !== 'pending' || $cuti->user_id !== Auth::id()) {
            return redirect()->route('cuti.index')->with('error', 'Tidak dapat mengedit cuti ini.');
        }
        
        return view('cuti.edit', compact('cuti'));
    }

    /**
     * Update cuti
     */
    public function update(Request $request, $id)
    {
        $cuti = Cuti::findOrFail($id);
        
        // Hanya bisa edit jika masih pending dan milik sendiri
        if ($cuti->status !== 'pending' || $cuti->user_id !== Auth::id()) {
            return redirect()->route('cuti.index')->with('error', 'Tidak dapat mengedit cuti ini.');
        }

        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'biro' => 'required|string|max:255',
            'jenis_cuti' => 'required|in:tahunan,sakit,melahirkan,penting,lainnya',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:10'
        ]);

        // Hitung jumlah hari cuti
        $tanggalMulai = \Carbon\Carbon::parse($validatedData['tanggal_mulai']);
        $tanggalSelesai = \Carbon\Carbon::parse($validatedData['tanggal_selesai']);
        $jumlahHari = $tanggalMulai->diffInDays($tanggalSelesai) + 1; // +1 karena termasuk hari pertama

        // Validasi maksimal 12 hari
        if ($jumlahHari > 12) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['tanggal_selesai' => 'Maksimal cuti adalah 12 hari. Anda mengajukan ' . $jumlahHari . ' hari.']);
        }

        $cuti->update($validatedData);

        return redirect()->route('cuti.index')->with('success', 'Pengajuan cuti berhasil diperbarui!');
    }

    /**
     * Hapus cuti
     */
    public function destroy($id)
    {
        $cuti = Cuti::findOrFail($id);
        
        // Hanya bisa hapus jika masih pending dan milik sendiri
        if ($cuti->status !== 'pending' || $cuti->user_id !== Auth::id()) {
            return redirect()->route('cuti.index')->with('error', 'Tidak dapat menghapus cuti ini.');
        }
        
        $cuti->delete();

        return redirect()->route('cuti.index')->with('success', 'Pengajuan cuti berhasil dihapus!');
    }

    /**
     * Approve cuti (admin only)
     */
    public function approve($id)
    {
        if (Auth::user()->role !== 'administrator') {
            abort(403, 'Unauthorized action.');
        }

        $cuti = Cuti::findOrFail($id);
        $cuti->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now()
        ]);

        // Buat notifikasi untuk user
        NotificationService::create(
            'Cuti Disetujui',
            'Pengajuan cuti ' . $cuti->nama . ' telah disetujui oleh ' . Auth::user()->name,
            'success',
            ['cuti_id' => $cuti->id]
        );

        return redirect()->route('cuti.index')->with('success', 'Cuti berhasil disetujui!');
    }

    /**
     * Reject cuti (admin only)
     */
    public function reject(Request $request, $id)
    {
        if (Auth::user()->role !== 'administrator') {
            abort(403, 'Unauthorized action.');
        }

        $validatedData = $request->validate([
            'rejection_reason' => 'required|string|min:5'
        ]);

        $cuti = Cuti::findOrFail($id);
        $cuti->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $validatedData['rejection_reason']
        ]);

        // Buat notifikasi untuk user
        NotificationService::create(
            'Cuti Ditolak',
            'Pengajuan cuti ' . $cuti->nama . ' ditolak oleh ' . Auth::user()->name . ': ' . $validatedData['rejection_reason'],
            'error',
            ['cuti_id' => $cuti->id]
        );

        return redirect()->route('cuti.index')->with('success', 'Cuti berhasil ditolak!');
    }

    /**
     * Dashboard cuti untuk admin
     */
    public function dashboard()
    {
        if (Auth::user()->role !== 'administrator') {
            abort(403, 'Unauthorized action.');
        }

        $pendingCount = Cuti::pending()->count();
        $approvedCount = Cuti::approved()->count();
        $rejectedCount = Cuti::rejected()->count();
        $recentCutis = Cuti::with('user')->orderBy('created_at', 'desc')->limit(5)->get();

        return view('cuti.dashboard', compact('pendingCount', 'approvedCount', 'rejectedCount', 'recentCutis'));
    }

    /**
     * Import data cuti dari PDF untuk menangkap jenis dan lama cuti
     */
    public function importFromPdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240'
        ]);

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($request->file('pdf_file')->getRealPath());
            $text = $pdf->getText();

            // Simpan versi uppercase dan versi asli
            $normalized = preg_replace('/\s+/', ' ', strtoupper($text));

            // Ambil Jenis Cuti
            $jenisCuti = null;
            $jenisMap = [
                'CUTI TAHUNAN' => 'tahunan',
                'CUTI SAKIT' => 'sakit',
                'CUTI MELAHIRKAN' => 'melahirkan',
                'CUTI PENTING' => 'penting',
            ];
            foreach (array_keys($jenisMap) as $key) {
                if (strpos($normalized, $key) !== false) {
                    $jenisCuti = $jenisMap[$key];
                    break;
                }
            }
            if (!$jenisCuti) {
                if (preg_match('/JENIS\s*CUTI\s*:\s*([^:]+?)\s{2,}/', $normalized, $m)) {
                    $raw = trim($m[1]);
                    $raw = preg_replace('/[^A-Z ]/', '', strtoupper($raw));
                    if (strpos($raw, 'TAHUN') !== false) $jenisCuti = 'tahunan';
                    elseif (strpos($raw, 'SAKIT') !== false) $jenisCuti = 'sakit';
                    elseif (strpos($raw, 'MELAHIR') !== false) $jenisCuti = 'melahirkan';
                    elseif (strpos($raw, 'PENTING') !== false) $jenisCuti = 'penting';
                }
            }
            if (!$jenisCuti) {
                $jenisCuti = 'tahunan';
            }

            // Ambil Lama Cuti (angka hari)
            $lamaCuti = null;
            if (preg_match('/LAMA\s*CUTI\s*:\s*(\d{1,2})/i', $normalized, $m)) {
                $lamaCuti = (int)$m[1];
            } elseif (preg_match('/SELAMA\s*(\d{1,2})\s*HARI/i', $normalized, $m)) {
                $lamaCuti = (int)$m[1];
            }

            // 1) Coba deteksi NRP terlebih dahulu (paling akurat)
            $members = Member::all();
            $detectedMember = null;
            $pdfNumbers = [];
            if (preg_match_all('/\b(\d{6,18})\b/', $text, $numMatches)) {
                $pdfNumbers = array_unique($numMatches[1]);
            }
            $matchedByNrp = [];
            foreach ($members as $memb) {
                $nrpDigits = preg_replace('/\D+/', '', (string) $memb->nrp);
                if ($nrpDigits === '') continue;
                foreach ($pdfNumbers as $num) {
                    if ($nrpDigits === $num) {
                        $matchedByNrp[] = $memb;
                        break;
                    }
                }
            }
            if (count($matchedByNrp) === 1) {
                $detectedMember = $matchedByNrp[0];
            }

            // 2) Jika belum ketemu, pakai pencocokan nama ketat (full exact setelah normalisasi)
            if (!$detectedMember) {
                $extractName = null;
                if (preg_match('/Nama\s*:\s*(.+)/i', $text, $nm)) {
                    $extractName = trim($nm[1]);
                } elseif (preg_match('/NAMA\s*:\s*([^:]+?)\s{2,}/', $normalized, $nm)) {
                    $extractName = trim($nm[1]);
                }
                $normalize = function ($name) {
                    $name = strtoupper($name);
                    // hapus gelar umum dan tanda baca
                    $name = str_replace([',', '.', '  '], ' ', $name);
                    $name = preg_replace('/\b(S\.?H\.?|S\.?IK\.?|S\.?\,?KOM\.?|A\.?Md\.?|M\.?T\.?|S\.?T\.?|S\.?IP\.?|S\.?Si\.?|S\.?Kom\.?)/i', '', $name);
                    $name = preg_replace('/[^A-Z ]/', ' ', $name);
                    $name = preg_replace('/\s+/', ' ', $name);
                    return trim($name);
                };
                if ($extractName) {
                    $normPdfName = $normalize($extractName);
                    foreach ($members as $memb) {
                        if ($normalize($memb->name) === $normPdfName) {
                            $detectedMember = $memb;
                            break;
                        }
                    }
                }
            }

            $result = [
                'filename' => $request->file('pdf_file')->getClientOriginalName(),
                'jenis_cuti' => $jenisCuti,
                'lama_cuti' => $lamaCuti,
                'member' => $detectedMember ? $detectedMember->name : null,
                'matched_by' => $detectedMember ? ($matchedByNrp ? 'nrp' : 'name') : null,
            ];

            // Buat record hanya jika match pasti (unik) dan lama_cuti valid
            if ($detectedMember && $lamaCuti && $lamaCuti > 0) {
                $start = now()->startOfDay();
                $end = (clone $start)->addDays(max(0, $lamaCuti - 1));

                // Simpan file pdf ke storage untuk dapat dipreview
                $storedPath = $request->file('pdf_file')->store('cuti-pdfs', 'public');
                $storedName = $request->file('pdf_file')->getClientOriginalName();

                Cuti::create([
                    'user_id' => Auth::id(),
                    'nama' => $detectedMember->name,
                    'biro' => '-',
                    'jenis_cuti' => $jenisCuti,
                    'tanggal_mulai' => $start->toDateString(),
                    'tanggal_selesai' => $end->toDateString(),
                    'alasan' => 'Import PDF: ' . $result['filename'],
                    'status' => 'approved',
                    'pdf_filename' => $storedName,
                    'pdf_path' => $storedPath,
                ]);

                return redirect()->route('cuti.sisa', ['year' => (int) now()->year])
                    ->with('import_result', $result)
                    ->with('success', 'Berhasil menambahkan cuti ' . $lamaCuti . ' hari untuk ' . $detectedMember->name . '.');
            }

            return redirect()->route('cuti.sisa')
                ->with('import_result', $result)
                ->with('error', 'PDF terbaca, namun anggota tidak bisa diidentifikasi secara pasti. Tidak ada data yang ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->route('cuti.sisa')->with('error', 'Gagal membaca PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get jenis cuti text
     */
    private function getJenisCutiText($jenis)
    {
        switch ($jenis) {
            case 'tahunan': return 'Tahunan';
            case 'sakit': return 'Sakit';
            case 'melahirkan': return 'Melahirkan';
            case 'penting': return 'Penting';
            default: return 'Lainnya';
        }
    }
}
