@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-calendar-event me-2"></i>Jadwal
        </h1>
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

    <div class="row schedule-container">
        @foreach($schedules as $schedule)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 schedule-card {{ $schedule->status === 'conflict' ? 'conflict-card' : '' }}" data-schedule-id="{{ $schedule->id }}" style="cursor: pointer; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $schedule->day }}</h5>
                    <span class="{{ $schedule->getStatusBadgeClass() }}">
                        {{ $schedule->getStatusText() }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="card-text"><strong>Tanggal:</strong> {{ $schedule->date->format('d-m-Y') }}</p>
                    <p class="card-text"><strong>Waktu:</strong> {{ $schedule->time }}</p>
                    <p class="card-text"><strong>Ruangan:</strong> {{ $schedule->room ?? 'Belum ditentukan' }}</p>
                    <p class="card-text"><strong>Kegiatan:</strong></p>
                    <ul class="list-unstyled">
                        @foreach($schedule->activities as $activity)
                            <li class="mb-1">
                                <small class="text-muted">{{ $activity['time'] }}</small><br>
                                {{ $activity['activity'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">Klik untuk melihat detail</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
.schedule-container {
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.schedule-card {
    border: 2px solid transparent;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: center;
    position: relative;
    z-index: 1;
    filter: blur(0px);
}

.schedule-card.conflict-card {
    border-color: #dc3545;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
}

.schedule-card.conflict-card .card-header {
    background-color: #dc3545;
    color: white;
}

.schedule-card.conflict-card .card-header .badge {
    background-color: white !important;
    color: #dc3545 !important;
}

.schedule-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
    border-color: #0f285f;
    z-index: 10;
    filter: blur(0px);
}

.schedule-card.selected {
    transform: translateY(-15px) scale(1.08);
    border-color: #0f285f;
    background-color: #ffffff;
    box-shadow: 0 20px 50px rgba(15, 40, 95, 0.3);
    z-index: 100;
    position: relative;
    filter: blur(0px) !important;
}

.schedule-card.blurred {
    filter: blur(2px);
    opacity: 0.6;
    transform: scale(0.95);
    pointer-events: none;
}

.schedule-card.selected::before {
    content: '';
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    background: linear-gradient(135deg, #0f285f, #1e40af);
    border-radius: 12px;
    z-index: -1;
    opacity: 0.1;
}

.schedule-card.selected .card-header {
    background: linear-gradient(135deg, #0f285f, #1e40af);
    color: white;
    border-radius: 8px 8px 0 0;
}

.schedule-card.selected .card-header .badge {
    background-color: rgba(255, 255, 255, 0.9) !important;
    color: #0f285f !important;
    font-weight: 600;
}

.schedule-card.selected .card-body {
    background-color: #ffffff;
    position: relative;
}

.schedule-card.selected .card-footer {
    background: linear-gradient(135deg, #e8f0ff, #f0f4ff);
    border-top: 2px solid #0f285f;
    border-radius: 0 0 8px 8px;
}

/* Overlay untuk background blur */
.overlay-blur {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(1px);
    z-index: 50;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.overlay-blur.active {
    opacity: 1;
    visibility: visible;
}

/* Alert Warning Styles */
.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
    border: 2px solid #ffc107 !important;
    color: #856404 !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2) !important;
}

.alert-warning .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleCards = document.querySelectorAll('.schedule-card');
    
    // Buat overlay untuk background blur
    const overlay = document.createElement('div');
    overlay.className = 'overlay-blur';
    document.body.appendChild(overlay);
    
    scheduleCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Hapus highlight dan blur dari semua card
            scheduleCards.forEach(c => {
                c.classList.remove('selected', 'blurred');
                c.style.zIndex = '1';
            });
            
            // Tambahkan highlight ke card yang diklik
            this.classList.add('selected');
            this.style.zIndex = '100';
            
            // Tambahkan blur ke card lain
            scheduleCards.forEach(c => {
                if (c !== this) {
                    c.classList.add('blurred');
                }
            });
            
            // Aktifkan overlay
            overlay.classList.add('active');
            
            // Simpan ID jadwal yang dipilih
            const scheduleId = this.getAttribute('data-schedule-id');
            console.log('Jadwal dipilih:', scheduleId);
        });
    });
    
    // Klik di overlay untuk menghilangkan highlight
    overlay.addEventListener('click', function() {
        scheduleCards.forEach(card => {
            card.classList.remove('selected', 'blurred');
            card.style.zIndex = '1';
        });
        overlay.classList.remove('active');
    });
    
    // Klik di luar card untuk menghilangkan highlight
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.schedule-card') && !e.target.closest('.overlay-blur')) {
            scheduleCards.forEach(card => {
                card.classList.remove('selected', 'blurred');
                card.style.zIndex = '1';
            });
            overlay.classList.remove('active');
        }
    });
});
</script>
@endsection
