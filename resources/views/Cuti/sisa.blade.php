@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<!-- DEBUG: File updated at {{ now() }} -->
<div class="container-fluid p-3">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <h4 class="mb-0"><i class="bi bi-people me-2"></i>Sisa Cuti Tahunan</h4>
        <form class="ms-auto d-flex gap-2" method="get">
            <div class="input-group" style="width: 220px;">
                <span class="input-group-text">Tahun</span>
                <input type="number" name="year" value="{{ $year }}" class="form-control">
                <button class="btn btn-primary" type="submit">Go</button>
            </div>
            <a href="{{ request()->fullUrlWithQuery(['year' => $year, 'reset' => 'all']) }}" class="btn btn-outline-danger" onclick="return confirm('Reset pemakaian cuti semua anggota untuk tahun {{ $year }}?');">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Semua
            </a>
        </form>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('import_result'))
        @php $r = session('import_result'); @endphp
        <div class="alert alert-info">
            <div><strong>File:</strong> {{ $r['filename'] }}</div>
            <div><strong>Jenis Cuti:</strong> {{ $r['jenis_cuti'] ?? 'Tidak terdeteksi' }}</div>
            <div><strong>Lama Cuti:</strong> {{ $r['lama_cuti'] ? $r['lama_cuti'] . ' hari' : 'Tidak terdeteksi' }}</div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('cuti.import-pdf') }}" method="post" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-6">
                    <input class="form-control" type="file" name="pdf_file" accept="application/pdf" required>
                </div>
                <div class="col-auto">
                    <button class="btn btn-secondary" type="submit"><i class="bi bi-file-earmark-pdf me-1"></i>Import PDF Cuti</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Pangkat/NRP</th>
                            <th>Jenis Cuti </th>
                            <th>Kuota</th>
                            <th>Terpakai</th>
                            <th>Sisa</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $idx => $row)
                        @php
                            $latest = \App\Models\Cuti::where('nama', $row['member']->name)
                                ->whereYear('tanggal_mulai', $year)
                                ->orderBy('tanggal_mulai', 'desc')
                                ->first();
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $row['member']->name }}</td>
                            <td>
                                <div>{{ $row['member']->rank ?? '' }} </div>
                                <div>{{ $row['member']->nrp ?? '' }}</div>
                            </td>
                            <td>{{ ucfirst($row['jenis']) }}</td>
                            <td><span class="badge bg-secondary">{{ $row['quota'] }} hari</span></td>
                            <td><span class="badge bg-warning">{{ $row['used'] }} hari</span></td>
                            <td><span class="badge {{ $row['remaining'] > 0 ? 'bg-success' : 'bg-danger' }}">{{ $row['remaining'] }} hari</span></td>
                            <td>
                                @if($latest && $latest->hasPdf())
                                    <a href="{{ $latest->getPdfUrl() }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Lihat PDF
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data anggota.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 