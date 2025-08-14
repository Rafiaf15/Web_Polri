@extends('main')

@section('title', 'Detail Cuti')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Pengajuan Cuti</h3>
                    <div class="card-tools">
                        <a href="{{ route('cuti.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        @if($cuti->status === 'pending' && $cuti->user_id === Auth::id())
                        <a href="{{ route('cuti.edit', $cuti->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Nama</strong></td>
                                    <td>: {{ $cuti->nama }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Biro</strong></td>
                                    <td>: {{ $cuti->biro }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Jenis Cuti</strong></td>
                                    <td>: 
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
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Mulai</strong></td>
                                    <td>: {{ $cuti->tanggal_mulai->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Selesai</strong></td>
                                    <td>: {{ $cuti->tanggal_selesai->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Jumlah Hari</strong></td>
                                    <td>: {{ $cuti->getJumlahHari() }} hari</td>
                                </tr>
                                <tr>
                                    <td><strong>Status</strong></td>
                                    <td>: 
                                        <span class="{{ $cuti->getStatusBadgeClass() }}">
                                            {{ $cuti->getStatusText() }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Tanggal Pengajuan</strong></td>
                                    <td>: {{ $cuti->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @if($cuti->status !== 'pending')
                                <tr>
                                    <td><strong>Disetujui Oleh</strong></td>
                                    <td>: {{ $cuti->approver->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Persetujuan</strong></td>
                                    <td>: {{ $cuti->approved_at ? $cuti->approved_at->format('d/m/Y H:i') : '-' }}</td>
                                </tr>
                                @if($cuti->status === 'rejected' && $cuti->rejection_reason)
                                <tr>
                                    <td><strong>Alasan Penolakan</strong></td>
                                    <td>: {{ $cuti->rejection_reason }}</td>
                                </tr>
                                @endif
                                @endif
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Alasan Cuti:</h5>
                            <div class="alert alert-info">
                                {{ $cuti->alasan }}
                            </div>
                        </div>
                    </div>

                    @if($cuti->status === 'pending' && Auth::user()->role === 'administrator')
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Aksi:</h5>
                            <button type="button" class="btn btn-success" onclick="approveCuti({{ $cuti->id }})">
                                <i class="fas fa-check"></i> Setujui
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectCuti({{ $cuti->id }})">
                                <i class="fas fa-times"></i> Tolak
                            </button>
                        </div>
                    </div>
                    @endif
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
