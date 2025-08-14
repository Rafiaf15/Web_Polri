@extends('main')

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-graph-up me-2"></i>Dashboard Cuti
        </h1>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6 mb-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-0">{{ $pendingCount }}</h3>
                            <p class="mb-0">Menunggu Persetujuan</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cuti.index') }}" class="text-white text-decoration-none">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-0">{{ $approvedCount }}</h3>
                            <p class="mb-0">Disetujui</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cuti.index') }}" class="text-white text-decoration-none">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-0">{{ $rejectedCount }}</h3>
                            <p class="mb-0">Ditolak</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-x-circle fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cuti.index') }}" class="text-white text-decoration-none">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-0">{{ $pendingCount + $approvedCount + $rejectedCount }}</h3>
                            <p class="mb-0">Total Pengajuan</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-calendar-event fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cuti.index') }}" class="text-white text-decoration-none">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pengajuan Cuti Terbaru</h5>
                    <a href="{{ route('cuti.index') }}" class="btn btn-primary">
                        <i class="bi bi-list me-1"></i>Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Jenis Cuti</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCutis as $index => $cuti)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $cuti->user->name }}</td>
                                    <td>
                                        @switch($cuti->jenis_cuti)
                                            @case('tahunan')
                                                <span class="badge badge-info">Cuti Tahunan</span>
                                                @break
                                            @case('sakit')
                                                <span class="badge badge-warning">Cuti Sakit</span>
                                                @break
                                            @case('melahirkan')
                                                <span class="badge badge-success">Cuti Melahirkan</span>
                                                @break
                                            @case('penting')
                                                <span class="badge badge-danger">Cuti Penting</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">Lainnya</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        {{ $cuti->tanggal_mulai->format('d/m/Y') }} - 
                                        {{ $cuti->tanggal_selesai->format('d/m/Y') }}
                                    </td>
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
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($cuti->status === 'pending')
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="approveCuti({{ $cuti->id }})">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="rejectCuti({{ $cuti->id }})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data cuti</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Pengajuan Cuti</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Alasan Penolakan</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
    $('#rejectModal').modal('show');
}
</script>
@endpush
