@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div class="d-flex align-items-center">
            <!-- Tombol Kembali -->
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <h1 class="h2 mb-0">
                <i class="bi bi-calendar-x me-2"></i>Daftar Pengajuan Cuti
            </h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cuti.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Ajukan Cuti
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            {{ session('error') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Biro</th>
                    <th>Jenis Cuti</th>
                    <th>Tanggal</th>
                    <th>Jumlah Hari</th>
                    <th>Status</th>
                    <th>Tanggal Pengajuan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cutis as $index => $cuti)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $cuti->nama }}</td>
                    <td>{{ $cuti->biro }}</td>
                    <td>
                        @switch($cuti->jenis_cuti)
                            @case('tahunan')
                                <span class="badge bg-info">Cuti Tahunan</span>
                                @break
                            @case('sakit')
                                <span class="badge bg-warning">Cuti Sakit</span>
                                @break
                            @case('melahirkan')
                                <span class="badge bg-success">Cuti Melahirkan</span>
                                @break
                            @case('penting')
                                <span class="badge bg-danger">Cuti Penting</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Lainnya</span>
                        @endswitch
                    </td>
                    <td>
                        {{ $cuti->tanggal_mulai->format('d/m/Y') }} - 
                        {{ $cuti->tanggal_selesai->format('d/m/Y') }}
                    </td>
                    <td>{{ $cuti->getJumlahHari() }} hari</td>
                    <td>
                        <span class="{{ $cuti->getStatusBadgeClass() }}">
                            {{ $cuti->getStatusText() }}
                        </span>
                    </td>
                    <td>{{ $cuti->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('cuti.show', $cuti->id) }}" 
                               class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            
                            @if($cuti->status === 'pending')
                                @if(Auth::user()->role === 'administrator')
                                <button type="button" class="btn btn-sm btn-success" 
                                        onclick="approveCuti({{ $cuti->id }})">
                                    <i class="bi bi-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="rejectCuti({{ $cuti->id }})">
                                    <i class="bi bi-x"></i>
                                </button>
                                @elseif($cuti->user_id === Auth::id())
                                <a href="{{ route('cuti.edit', $cuti->id) }}" 
                                   class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="deleteCuti({{ $cuti->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data cuti</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Tolak Pengajuan Cuti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Alasan Penolakan</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form untuk approve -->
<form id="approveForm" method="POST" style="display: none;">
    @csrf
</form>

<!-- Form untuk delete -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function approveCuti(id) {
    if (confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')) {
        const form = document.getElementById('approveForm');
        form.action = `/cuti/${id}/approve`;
        form.submit();
    }
}

function rejectCuti(id) {
    const form = document.getElementById('rejectForm');
    form.action = `/cuti/${id}/reject`;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

function deleteCuti(id) {
    if (confirm('Apakah Anda yakin ingin menghapus pengajuan cuti ini?')) {
        const form = document.getElementById('deleteForm');
        form.action = `/cuti/${id}`;
        form.submit();
    }
}
</script>
@endpush