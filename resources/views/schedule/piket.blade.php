@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-calendar-week me-2"></i>Jadwal Piket
        </h1>
        <p class="text-muted mb-0">Jadwal piket Divisi TIK POLRI</p>
    </div>

    <!-- Card Jadwal Piket -->
    <div class="row g-4">
        @foreach($schedules as $schedule)
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $schedule->day }}</h5>
                        <span class="{{ $schedule->getStatusBadgeClass() }}">
                            {{ $schedule->getStatusText() }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Tanggal</small>
                        <div class="fw-bold">{{ $schedule->date->format('d-m-Y') }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Waktu</small>
                        <div class="fw-bold">{{ $schedule->time }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Kegiatan</small>
                        <ul class="list-unstyled mb-0">
                            @foreach($schedule->activities as $activity)
                            <li class="mb-1">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <small class="text-muted">{{ $activity['time'] }}</small><br>
                                {{ $activity['activity'] }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex gap-2">
                        <a href="{{ route('schedule.edit', $schedule->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                        <form action="{{ route('schedule.destroy', $schedule->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus jadwal ini?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection 