@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-calendar-check me-2"></i>Jadwal Edit
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('schedule.import-pdf') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-pdf me-1"></i>Import PDF
            </a>
            <a href="{{ route('schedule.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Jadwal
            </a>
        </div>
    </div>

    <!-- Alert Warning untuk Jadwal yang Bentrok -->
    @php
        $conflictSchedules = $schedules->where('status', 'conflict');
    @endphp
    
    @if($conflictSchedules->count() > 0)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
            <div>
                <strong>Peringatan Jadwal Bentrok!</strong>
                <p class="mb-0 mt-1">Ditemukan {{ $conflictSchedules->count() }} jadwal yang memiliki konflik waktu:</p>
                <ul class="mb-0 mt-1">
                    @foreach($conflictSchedules as $schedule)
                        <li><strong>{{ $schedule->day }}</strong> ({{ $schedule->date->format('d-m-Y') }}) - {{ $schedule->time }}</li>
                    @endforeach
                </ul>
                <small class="text-muted">Silakan periksa dan sesuaikan jadwal untuk menghindari bentrok.</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('schedule.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="day" class="form-label">Hari</label>
                    <select name="day" id="day" class="form-select">
                        <option value="">Semua Hari</option>
                        <option value="Senin" {{ request('day') == 'Senin' ? 'selected' : '' }}>Senin</option>
                        <option value="Selasa" {{ request('day') == 'Selasa' ? 'selected' : '' }}>Selasa</option>
                        <option value="Rabu" {{ request('day') == 'Rabu' ? 'selected' : '' }}>Rabu</option>
                        <option value="Kamis" {{ request('day') == 'Kamis' ? 'selected' : '' }}>Kamis</option>
                        <option value="Jumat" {{ request('day') == 'Jumat' ? 'selected' : '' }}>Jumat</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Tidak Bentrok</option>
                        <option value="conflict" {{ request('status') == 'conflict' ? 'selected' : '' }}>Bentrok</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Tanggal</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="{{ route('schedule.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Hari</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Ruangan</th>
                            <th>Kegiatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $index => $schedule)
                        <tr class="{{ $schedule->status === 'conflict' ? 'table-warning' : '' }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $schedule->day }}</td>
                            <td>{{ $schedule->date->format('d-m-Y') }}</td>
                            <td>{{ $schedule->time }}</td>
                            <td>{{ $schedule->room ?? 'Belum ditentukan' }}</td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    @foreach($schedule->activities as $activity)
                                        <li class="mb-1">
                                            <small class="text-muted">{{ $activity['time'] }}</small><br>
                                            {{ $activity['activity'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                <span class="{{ $schedule->getStatusBadgeClass() }}">
                                    {{ $schedule->getStatusText() }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('schedule.edit', $schedule->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('schedule.destroy', $schedule->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus jadwal ini?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada jadwal yang ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 