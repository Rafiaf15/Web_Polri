@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-plus-circle me-2"></i>Tambah Jadwal Baru
        </h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('schedule.store') }}" method="POST">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="day" class="form-label">Hari</label>
                        <select name="day" id="day" class="form-select" required>
                            <option value="">Pilih Hari</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Tanggal</label>
                        <input type="date" name="date" id="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="time" class="form-label">Waktu</label>
                        <input type="text" name="time" id="time" class="form-control" placeholder="08:00 - 16:00" required>
                    </div>
                    <div class="col-md-3">
                        <label for="room" class="form-label">Ruangan</label>
                        <input type="text" name="room" id="room" class="form-control" placeholder="Ruang Meeting Lt. 1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kegiatan</label>
                    <div id="activities-container">
                        <div class="row mb-2 activity-row">
                            <div class="col-md-6">
                                <input type="text" name="activities[0][activity]" class="form-control" placeholder="Nama kegiatan" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="activities[0][time]" class="form-control" placeholder="08:00 - 09:00" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-activity">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" id="add-activity">
                        <i class="bi bi-plus"></i> Tambah Kegiatan
                    </button>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('schedule.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let activityIndex = 1;
    
    // Function to get day name from date
    function getDayFromDate(dateString) {
        const date = new Date(dateString);
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return days[date.getDay()];
    }
    
    // Function to update day when date changes
    function updateDayFromDate() {
        const dateInput = document.getElementById('date');
        const daySelect = document.getElementById('day');
        
        if (dateInput.value) {
            const dayName = getDayFromDate(dateInput.value);
            daySelect.value = dayName;
        }
    }
    
    // Add event listener to date input
    document.getElementById('date').addEventListener('change', updateDayFromDate);
    
    document.getElementById('add-activity').addEventListener('click', function() {
        const container = document.getElementById('activities-container');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 activity-row';
        newRow.innerHTML = `
            <div class="col-md-6">
                <input type="text" name="activities[${activityIndex}][activity]" class="form-control" placeholder="Nama kegiatan" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="activities[${activityIndex}][time]" class="form-control" placeholder="08:00 - 09:00" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-activity">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        activityIndex++;
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-activity')) {
            e.target.closest('.activity-row').remove();
        }
    });
});
</script>
@endsection 