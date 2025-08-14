@extends('main')

@section('title', 'Edit Cuti')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Pengajuan Cuti</h3>
                    <div class="card-tools">
                        <a href="{{ route('cuti.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('cuti.update', $cuti->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Nama <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nama') is-invalid @enderror" 
                                           id="nama" name="nama" 
                                           value="{{ old('nama', $cuti->nama) }}" required>
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="biro">Biro <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('biro') is-invalid @enderror" 
                                           id="biro" name="biro" 
                                           value="{{ old('biro', $cuti->biro) }}" required>
                                    @error('biro')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jenis_cuti">Jenis Cuti <span class="text-danger">*</span></label>
                                    <select class="form-control @error('jenis_cuti') is-invalid @enderror" 
                                            id="jenis_cuti" name="jenis_cuti" required>
                                        <option value="">Pilih Jenis Cuti</option>
                                        <option value="tahunan" {{ old('jenis_cuti', $cuti->jenis_cuti) == 'tahunan' ? 'selected' : '' }}>
                                            Cuti Tahunan
                                        </option>
                                        <option value="sakit" {{ old('jenis_cuti', $cuti->jenis_cuti) == 'sakit' ? 'selected' : '' }}>
                                            Cuti Sakit
                                        </option>
                                        <option value="melahirkan" {{ old('jenis_cuti', $cuti->jenis_cuti) == 'melahirkan' ? 'selected' : '' }}>
                                            Cuti Melahirkan
                                        </option>
                                        <option value="penting" {{ old('jenis_cuti', $cuti->jenis_cuti) == 'penting' ? 'selected' : '' }}>
                                            Cuti Penting
                                        </option>
                                        <option value="lainnya" {{ old('jenis_cuti', $cuti->jenis_cuti) == 'lainnya' ? 'selected' : '' }}>
                                            Lainnya
                                        </option>
                                    </select>
                                    @error('jenis_cuti')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror" 
                                           id="tanggal_mulai" name="tanggal_mulai" 
                                           value="{{ old('tanggal_mulai', $cuti->tanggal_mulai->format('Y-m-d')) }}" required>
                                    @error('tanggal_mulai')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('tanggal_selesai') is-invalid @enderror" 
                                           id="tanggal_selesai" name="tanggal_selesai" 
                                           value="{{ old('tanggal_selesai', $cuti->tanggal_selesai->format('Y-m-d')) }}" required>
                                    @error('tanggal_selesai')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jumlah Hari</label>
                                    <input type="text" class="form-control" id="jumlah_hari" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="alasan">Alasan Cuti <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('alasan') is-invalid @enderror" 
                                      id="alasan" name="alasan" rows="4" 
                                      placeholder="Jelaskan alasan pengajuan cuti..." required>{{ old('alasan', $cuti->alasan) }}</textarea>
                            @error('alasan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Cuti
                            </button>
                            <a href="{{ route('cuti.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    const jumlahHari = document.getElementById('jumlah_hari');

    function hitungJumlahHari() {
        if (tanggalMulai.value && tanggalSelesai.value) {
            const mulai = new Date(tanggalMulai.value);
            const selesai = new Date(tanggalSelesai.value);
            
            if (selesai >= mulai) {
                const diffTime = Math.abs(selesai - mulai);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                jumlahHari.value = diffDays + ' hari';
            } else {
                jumlahHari.value = 'Tanggal selesai harus setelah tanggal mulai';
            }
        } else {
            jumlahHari.value = '';
        }
    }

    tanggalMulai.addEventListener('change', hitungJumlahHari);
    tanggalSelesai.addEventListener('change', hitungJumlahHari);

    // Hitung jumlah hari saat halaman dimuat
    hitungJumlahHari();

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    tanggalMulai.min = today;
    tanggalSelesai.min = today;
});
</script>
@endpush
