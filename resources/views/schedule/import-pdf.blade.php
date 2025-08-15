@extends('main')

@section('sidebar')
@include('sidebar_dashboard')
@endsection

@section('content')
<div class="container-fluid p-3">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <h4 class="mb-0"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import Jadwal dari PDF</h4>
    </div>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info">
                        <div class="fw-semibold mb-1">Format yang didukung:</div>
                        <ul class="mb-2">
                            <li>Surat: Hari/Tanggal, Pukul (WIB, s.d. selesai), Tempat, Perihal/Kegiatan</li>
                            <li>Tabel: Tanggal | Waktu | Ruang | Kegiatan</li>
                        </ul>
                    </div>

                    <form action="{{ route('schedule.import-pdf') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label">Pilih File PDF</label>
                            <input type="file" class="form-control @error('pdf_file') is-invalid @enderror" id="pdf_file" name="pdf_file" accept="application/pdf" required>
                            @error('pdf_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maks 10MB. Disarankan PDF teks (bukan hasil scan gambar).</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" id="previewBtn">
                                <i class="bi bi-eye"></i> Preview PDF
                            </button>
                            <button type="button" class="btn btn-warning" id="extractTestBtn">
                                <i class="bi bi-search"></i> Test Ekstraksi
                            </button>
                            <button type="submit" class="btn btn-primary" id="importBtn">
                                <i class="bi bi-upload"></i> Import Sekarang
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="debugRegexBtn">
                                <i class="bi bi-bug"></i> Debug Regex
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Preview PDF</div>
                <div class="card-body p-0" style="height:480px;">
                    <iframe id="pdfViewer" title="PDF Preview" style="width:100%;height:100%;border:0;" src="about:blank"></iframe>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">Hasil Ekstraksi</div>
                <div class="card-body">
                    <pre id="extractResult" class="small text-muted" style="white-space:pre-wrap;">Belum ada hasil.</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const fileInput = document.getElementById('pdf_file');
    const viewer = document.getElementById('pdfViewer');
    const previewBtn = document.getElementById('previewBtn');
    const extractBtn = document.getElementById('extractTestBtn');
    const debugBtn = document.getElementById('debugRegexBtn');
    const extractResult = document.getElementById('extractResult');

    function ensureFile(){
        if(!fileInput.files || !fileInput.files[0]){
            alert('Silakan pilih file PDF terlebih dahulu.');
            return false;
        }
        return true;
    }

    previewBtn.addEventListener('click', function(){
        if(!ensureFile()) return;
        const url = URL.createObjectURL(fileInput.files[0]);
        viewer.src = url + '#view=FitH';
    });

    extractBtn.addEventListener('click', async function(){
        if(!ensureFile()) return;
        const fd = new FormData();
        fd.append('pdf_file', fileInput.files[0]);
        try {
            const res = await fetch('{{ route('schedule.preview-pdf') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                body: fd
            });
            const data = await res.json();
            if(data.success){
                renderExtraction(data);
            } else {
                extractResult.textContent = 'Gagal: ' + (data.message || 'Tidak diketahui');
            }
        } catch (e) {
            extractResult.textContent = 'Kesalahan jaringan: ' + e.message;
        }
    });

    debugBtn.addEventListener('click', async function(){
        if(!ensureFile()) return;
        const fd = new FormData();
        fd.append('pdf_file', fileInput.files[0]);
        try {
            const res = await fetch('{{ route('schedule.debug-regex') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                body: fd
            });
            const data = await res.json();
            extractResult.textContent = JSON.stringify(data, null, 2);
        } catch (e) {
            extractResult.textContent = 'Kesalahan jaringan: ' + e.message;
        }
    });

    function renderExtraction(payload){
        const schedules = payload.schedules || [];
        const debug = payload.debug || {};
        if(schedules.length === 0){
            extractResult.textContent = 'Tidak ada jadwal terdeteksi. Pastikan surat memuat Hari/Tanggal, Pukul, Tempat, dan Hal/Perihal.';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr>'+
                   '<th>Hari</th><th>Tanggal</th><th>Waktu</th><th>Tempat</th><th>Kegiatan</th></tr></thead><tbody>';
        schedules.forEach(s => {
            const act = (s.activities && s.activities[0] && s.activities[0].activity) || '';
            html += `<tr><td>${s.day||''}</td><td>${s.date||''}</td><td>${s.time||''}</td><td>${s.room||''}</td><td>${act}</td></tr>`;
        });
        html += '</tbody></table></div>';
        extractResult.innerHTML = html;
    }
})();
</script>
@endpush
