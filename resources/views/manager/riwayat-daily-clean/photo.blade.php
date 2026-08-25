@extends('layouts.role')
@section('title', $title ?? 'Detail Foto Daily Clean - Ethikopia Stock Management')

@section('content')
<div class="page-container">
    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-image me-2"></i>Detail Foto Daily Clean
                    </h2>
                    <a href="{{ route('manager.daily-clean.detail', $record->id) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail Daily Clean
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="row mb-3">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Nama File</small>
                            <strong>{{ $photo->original_name ?? 'Foto' }}</strong>
                        </div>
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Waktu Upload</small>
                            <strong>{{ $photo->created_at ? $photo->created_at->format('Y-m-d H:i:s') : '-' }}</strong>
                        </div>
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Shift</small>
                            <strong>{{ $record->shift }}</strong>
                        </div>
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Barista</small>
                            <strong>{{ $record->barista }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Photo Container --}}
    <div class="row">
        <div class="col-12 col-lg-10 mx-auto text-center">
            @if (!empty($url))
                <div class="card border-0 shadow-sm bg-dark p-3">
                    <img src="{{ $url }}" 
                         class="img-fluid rounded" 
                         style="max-height:75vh; object-fit:contain; width:100%;" 
                         alt="{{ $photo->original_name ?? 'Foto' }}"
                         onerror="this.style.display='none';document.getElementById('photo-error').classList.remove('d-none');document.getElementById('photo-error').classList.add('d-flex');">
                    
                    <div id="photo-error" class="d-none flex-column align-items-center justify-content-center rounded border" style="height:400px;width:100%;background:#2a2a2e;">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size:3rem;"></i>
                        <p class="text-muted mt-2">Gagal memuat foto. File mungkin tidak tersedia di server.</p>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm bg-dark p-3">
                    <div class="d-flex flex-column align-items-center justify-content-center rounded border" style="height:400px;width:100%;background:#2a2a2e;">
                        <i class="bi bi-image-alt text-muted" style="font-size:3rem;"></i>
                        <p class="text-muted mt-2">Foto tidak tersedia.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
