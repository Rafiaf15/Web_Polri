@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<div class="container-fluid p-3">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <h4 class="mb-0"><i class="bi bi-people me-2"></i>Sisa Cuti Tahunan</h4>
        <form class="ms-auto" method="get">
            <div class="input-group" style="width: 220px;">
                <span class="input-group-text">Tahun</span>
                <input type="number" name="year" value="{{ $year }}" class="form-control">
                <button class="btn btn-primary" type="submit">Go</button>
            </div>
        </form>
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
                            <th>Jabatan</th>
                            <th>Kuota</th>
                            <th>Terpakai</th>
                            <th>Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $idx => $row)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $row['member']->name }}</td>
                            <td>{{ trim(($row['member']->rank ?? '') . ' ' . ($row['member']->nrp ?? '')) }}</td>
                            <td>{{ $row['member']->position }}</td>
                            <td><span class="badge bg-secondary">{{ $row['quota'] }} hari</span></td>
                            <td><span class="badge bg-warning">{{ $row['used'] }} hari</span></td>
                            <td><span class="badge {{ $row['remaining'] > 0 ? 'bg-success' : 'bg-danger' }}">{{ $row['remaining'] }} hari</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Belum ada data anggota.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection


