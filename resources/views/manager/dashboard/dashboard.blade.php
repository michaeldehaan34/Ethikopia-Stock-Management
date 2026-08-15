@extends('layouts.role')
@section('title', 'Dashboard Manager - Ethikopia Stock Management')
@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-0">
                        <i class="bi bi-speedometer2 me-2 text-teal"></i>
                        Dashboard Ethikopia Stock Management
                    </h2>
                    <p class="text-muted mb-0">Selamat datang, {{ $managerName }}! Berikut Ringkasan Stock Ethikopia.</p>
                </div>
            </div>
        </div>
    </div>

    @if(!$has_data)
    <!-- Belum ada data -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <div class="empty-title">Belum ada data</div>
                        <div class="empty-text">Mulai catat update stok untuk menampilkan ringkasan di sini.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else

    <!-- 1. Ringkasan Statistik -->
    <div class="row g-4 mb-4">
        <!-- Barang Aman -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                Barang Aman
                            </h6>
                            <h2 class="mb-0 fw-bold text-success">{{ $bahan_aman }}</h2>
                            <small class="text-muted">item stok > {{ $limit_tipis }}</small>
                        </div>
                        <div class="icon-box icon-success">
                            <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barang Tipis -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                Barang Tipis
                            </h6>
                            <h2 class="mb-0 fw-bold text-amber">{{ $bahan_tipis }}</h2>
                            <small class="text-muted">item stok > {{ $limit_habis }} dan &le; {{ $limit_tipis }}</small>
                        </div>
                        <div class="icon-box icon-amber">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barang Habis -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                Barang Habis
                            </h6>
                            <h2 class="mb-0 fw-bold text-rose">{{ $bahan_habis }}</h2>
                            <small class="text-muted">item stok kosong / &le; {{ $limit_habis }}</small>
                        </div>
                        <div class="icon-box icon-rose">
                            <i class="bi bi-x-circle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STOK SAAT INI -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-box-seam me-2 text-primary"></i>
                            Stok Saat Ini
                        </h5>
                        <div class="w-100 ms-3" style="max-width: 300px;">
                            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari bahan baku...">
                        </div>
                    </div>
                    @if(count($stok_saat_ini ?? []) > 0)
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="stokTable">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>Nama Bahan</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Satuan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stok_saat_ini as $item)
                                <tr class="stok-row">
                                    <td class="bahan-nama">{{ $item['nama'] }}</td>
                                    <td><strong>{{ $item['stok'] }}</strong></td>
                                    <td><span class="text-muted">{{ $item['satuan'] }}</span></td>
                                    <td>
                                        @if($item['status'] == 'aman')
                                        <span class="badge bg-success">Aman</span>
                                        @elseif($item['status'] == 'tipis')
                                        <span class="badge bg-warning text-dark">Limit</span>
                                        @else
                                        <span class="badge bg-danger">Habis</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Belum ada data stok.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <!-- 4. Aktivitas Barista -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="bi bi-people-fill me-2 text-teal"></i>
                        Aktivitas Barista
                    </h5>
                    @if(count($top_aktivitas_barista) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">No</th>
                                    <th>Nama Barista</th>
                                    <th style="width: 24%;">Jumlah Update Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_aktivitas_barista as $item)
                                <tr>
                                    <td>{{ $item['no'] }}</td>
                                    <td>{{ $item['nama_barista'] }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $item['jumlah'] }} update</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Belum ada aktivitas barista tercatat.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll('.stok-row');
                
                rows.forEach(row => {
                    const namaBahan = row.querySelector('.bahan-nama').textContent.toLowerCase();
                    if (namaBahan.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endsection