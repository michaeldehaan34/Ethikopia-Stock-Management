@extends('layouts.role')

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-0">
                        <i class="bi bi-graph-up me-2 text-teal"></i>
                        Forecast Mingguan
                    </h2>
                    <p class="text-muted mb-0">
                        Prediksi kebutuhan stok.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Filter Periode -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('manager.forecast') }}">
                        <div class="mb-3">
                            <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                            <input type="date" class="form-control" id="tanggal_awal"
                                   name="tanggal_awal" value="{{ $tanggal_awal ?? '' }}">
                        </div>

                        <div class="mb-4">
                            <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="tanggal_akhir"
                                   name="tanggal_akhir" value="{{ $tanggal_akhir ?? '' }}">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-graph-up me-2"></i>
                                Tampilkan Forecast
                            </button>
                            @if($periode_valid)
                            <a href="{{ route('manager.forecast.export-excel', ['tanggal_awal' => $tanggal_awal, 'tanggal_akhir' => $tanggal_akhir]) }}" class="btn btn-outline-light">
                                <i class="bi bi-file-earmark-excel me-2"></i>
                                Export Excel
                            </a>
                            <a href="{{ route('manager.forecast.export-pdf', ['tanggal_awal' => $tanggal_awal, 'tanggal_akhir' => $tanggal_akhir]) }}" class="btn btn-outline-light">
                                <i class="bi bi-file-earmark-pdf me-2"></i>
                                Export PDF
                            </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Forecast -->
    @if($periode_valid)
    <!-- Ringkasan -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Periode</h6>
                    <h4 class="mb-0 fw-bold">{{ $tanggal_awal }} s.d. {{ $tanggal_akhir }}</h4>
                    <small class="text-muted">{{ $jumlah_hari }} hari</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Total Kebutuhan</h6>
                    <h2 class="mb-0 fw-bold text-amber">{{ $total_kebutuhan }}</h2>
                    <small class="text-muted">akumulasi konsumsi periode</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Estimasi Pembelian</h6>
                    <h2 class="mb-0 fw-bold text-teal">{{ $total_estimasi_pembelian }}</h2>
                    <small class="text-muted">kebutuhan dikurangi stok sekarang</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Detail Forecast (struktur Kategori -> Kelompok -> Barang) -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="bi bi-clipboard-data me-2 text-teal"></i>
                        Detail Forecast per Barang
                    </h5>

                    @foreach($items_tree as $node)
<h6 class="mt-3 mb-2 fw-bold text-brown">{{ $node['kategori'] }}</h6>
                    @foreach($node['kelompok_list'] as $grp)
                    <div class="mb-2">
                        <div class="fw-semibold text-teal mb-1">{{ $grp['kelompok'] }}</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th>Nama Barang</th>
                                        <th>Stok Sekarang</th>
                                        <th>Forecast Kebutuhan</th>
                                        <th>Estimasi Pembelian</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($grp['items'] as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item['nama_barang'] }}</td>
                                        <td>{{ $item['stok_sekarang'] }}</td>
                                        <td>{{ $item['kebutuhan'] }}</td>
                                        <td>{{ $item['estimasi_pembelian'] }}</td>
                                        <td>
                                            @if($item['stok_sekarang'] <= $item['limit_habis'])
                                            <span class="badge rounded-pill text-bg-danger px-3 py-2">
                                                <i class="bi bi-x-octagon me-1"></i>Habis
                                            </span>
                                            @elseif($item['stok_sekarang'] <= $item['limit_tipis'])
                                            <span class="badge rounded-pill text-bg-warning px-3 py-2">
                                                <i class="bi bi-cart-plus me-1"></i>Perlu Dibeli
                                            </span>
                                            @else
                                            <span class="badge rounded-pill text-bg-success px-3 py-2">
                                                <i class="bi bi-check-circle me-1"></i>Aman
                                            </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @elseif($tanggal_awal || $tanggal_akhir)
    <!-- Periode tidak valid -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div class="empty-title">Periode tidak valid</div>
                        <div class="empty-text">
                            @if(!($tanggal_awal && $tanggal_akhir))
                                Silakan pilih rentang tanggal terlebih dahulu.
                            @else
                                Tanggal Akhir tidak boleh lebih kecil dari Tanggal Awal. Pastikan Tanggal Awal &le; Tanggal Akhir.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Belum ada filter -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-hourglass-split"></i>
                        <div class="empty-title">Belum ada filter</div>
                        <div class="empty-text">Silakan pilih rentang tanggal terlebih dahulu.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

