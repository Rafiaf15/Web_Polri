<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cuti;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class CutiController extends Controller
{
    /**
     * Tampilkan daftar cuti
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'administrator') {
            // Admin melihat semua cuti
            $cutis = Cuti::with('user')->orderBy('created_at', 'desc')->get();
        } else {
            // User biasa hanya melihat cuti sendiri
            $cutis = Cuti::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        }
        
        return view('cuti.index', compact('cutis'));
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
