<?php

use App\Http\Controllers\BaristaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\MasterBahanController;
use App\Http\Controllers\StokMasukController;
use Illuminate\Support\Facades\Route;

// Root entry point (preserves the login page design).
// - If the user is NOT logged in, redirect to the login page.
// - If the user IS logged in, redirect to the dashboard according to their role.
Route::get('/', function () {
    if (session()->has('username')) {
        if (session('role') === 'manager') {
            return redirect()->route('manager.dashboard');
        }

        return redirect()->route('barista.dashboard');
    }

    return redirect()->route('login');
});

/*
 * Barista features.
 * Accessible by Barista and Manager (the Manager role has full access).
 */
Route::middleware(['session.auth', 'role:barista,manager'])->group(function () {
    Route::get('/barista/dashboard', [BaristaController::class, 'dashboard'])
        ->name('barista.dashboard');

    Route::get('/barista/stok-masuk', [BaristaController::class, 'stokMasuk'])
        ->name('barista.stok-masuk');

    Route::get('/barista/update-stok', [BaristaController::class, 'updateStok'])
        ->name('barista.update-stok');

    Route::post('/barista/update-stok', [BaristaController::class, 'updateStokStore'])
        ->name('barista.update-stok.store');

    Route::get('/barista/daily-clean', [BaristaController::class, 'dailyClean'])
        ->name('barista.daily-clean');

    Route::get('/barista/token-listrik', [BaristaController::class, 'tokenListrik'])
        ->name('barista.token-listrik');

    // POST store endpoints (referenced by barista forms; methods exist in
    // BaristaController but were missing from the route definitions).
    Route::post('/barista/stok-masuk/store', [BaristaController::class, 'stokMasukStore'])
        ->name('barista.stok-masuk.store');

    Route::post('/barista/daily-clean/store', [BaristaController::class, 'dailyCleanStore'])
        ->name('barista.daily-clean.store');

    Route::post('/barista/token-listrik/store', [BaristaController::class, 'tokenListrikStore'])
        ->name('barista.token-listrik.store');
});

/*
 * Manager features.
 * Manager-only (full access).
 */
Route::middleware(['session.auth', 'role:manager'])->group(function () {
    Route::get('/manager/dashboard', [DashboardController::class, 'dashboard'])
        ->name('manager.dashboard');


    Route::get('/manager/riwayat/stok-masuk', [StokMasukController::class, 'index'])
        ->name('manager.stok-masuk.index');
    Route::get('/manager/stok-masuk/create', [StokMasukController::class, 'create'])
        ->name('manager.stok-masuk.create');
    Route::post('/manager/stok-masuk/store', [StokMasukController::class, 'store'])
        ->name('manager.stok-masuk.store');
    Route::get('/manager/stok-masuk/detail/{id}', [StokMasukController::class, 'detail'])
        ->name('manager.stok-masuk.detail');
    Route::get('/manager/stok-masuk/edit/{id}', [StokMasukController::class, 'edit'])
        ->name('manager.stok-masuk.edit');
    Route::post('/manager/stok-masuk/update/{id}', [StokMasukController::class, 'update'])
        ->name('manager.stok-masuk.update');
    Route::post('/manager/stok-masuk/hapus/{id}', [StokMasukController::class, 'destroy'])
        ->name('manager.stok-masuk.delete');

    Route::get('/manager/riwayat/update-stok', [ManagerController::class, 'riwayatUpdateStok'])
        ->name('manager.riwayat.update-stok');
    Route::get('/manager/update-stok/detail/{id}', [ManagerController::class, 'updateStokDetail'])
        ->name('manager.update-stok.detail');
    Route::get('/manager/update-stok/edit/{id}', [ManagerController::class, 'updateStokEdit'])
        ->name('manager.update-stok.edit');
    Route::put('/manager/update-stok/update/{id}', [ManagerController::class, 'updateStokUpdate'])
        ->name('manager.update-stok.update');
    Route::delete('/manager/update-stok/hapus/{id}', [ManagerController::class, 'updateStokDestroy'])
        ->name('manager.update-stok.delete');

    Route::get('/manager/riwayat/daily-clean', [ManagerController::class, 'riwayatDailyClean'])
        ->name('manager.riwayat.daily-clean');

    Route::get('/manager/riwayat/token-listrik', [ManagerController::class, 'riwayatTokenListrik'])
        ->name('manager.riwayat.token-listrik');

    Route::get('/manager/data-barista', [ManagerController::class, 'dataBarista'])
        ->name('manager.data-barista');

    Route::get('/manager/master-bahan', [MasterBahanController::class, 'index'])
        ->name('manager.master-bahan');

    Route::get('/manager/master-bahan/create', [MasterBahanController::class, 'create'])
        ->name('manager.master-bahan.create');

    Route::get('/manager/master-bahan/detail/{id}', [MasterBahanController::class, 'detail'])
        ->name('manager.master-bahan.detail');

    Route::get('/manager/master-bahan/edit/{id}', [MasterBahanController::class, 'edit'])
        ->name('manager.master-bahan.edit');

    Route::get('/manager/pengaturan-limit', [ManagerController::class, 'pengaturanLimit'])
        ->name('manager.pengaturan-limit');

    Route::get('/manager/laporan', [ManagerController::class, 'laporan'])
        ->name('manager.laporan');

    Route::get('/manager/forecast', [ManagerController::class, 'forecast'])
        ->name('manager.forecast');

    // Data Barista (CRUD)
    Route::get('/manager/data-barista/create', [ManagerController::class, 'baristaCreate'])
        ->name('manager.data-barista.create');
    Route::post('/manager/data-barista/store', [ManagerController::class, 'baristaStore'])
        ->name('manager.data-barista.store');
    Route::get('/manager/data-barista/detail/{id}', [ManagerController::class, 'baristaDetail'])
        ->name('manager.data-barista.detail');
    Route::get('/manager/data-barista/edit/{id}', [ManagerController::class, 'baristaEditForm'])
        ->name('manager.data-barista.edit.form');
    Route::post('/manager/data-barista/tambah', [ManagerController::class, 'baristaAdd'])
        ->name('manager.data-barista.add');
    Route::post('/manager/data-barista/edit/{id}', [ManagerController::class, 'baristaEdit'])
        ->name('manager.data-barista.edit');
    Route::post('/manager/data-barista/hapus/{id}', [ManagerController::class, 'baristaDelete'])
        ->name('manager.data-barista.delete');

    // Master Bahan (CRUD + toggle + kelompok) - Tahap 6
    Route::get('/manager/master-bahan/kelompok', [MasterBahanController::class, 'kelompok'])
        ->name('manager.master-bahan.kelompok');
    Route::post('/manager/master-bahan/tambah', [MasterBahanController::class, 'store'])
        ->name('manager.master-bahan.add');
    Route::post('/manager/master-bahan/edit/{id}', [MasterBahanController::class, 'update'])
        ->name('manager.master-bahan.update');
    Route::post('/manager/master-bahan/hapus/{id}', [MasterBahanController::class, 'destroy'])
        ->name('manager.master-bahan.delete');
    Route::post('/manager/master-bahan/status/{id}', [MasterBahanController::class, 'toggle'])
        ->name('manager.master-bahan.toggle');

    // Pengaturan Limit
    Route::post('/manager/pengaturan-limit/simpan', [ManagerController::class, 'pengaturanLimitSimpan'])
        ->name('manager.pengaturan-limit.simpan');
    Route::get('/manager/pengaturan-limit/edit/{id}', [ManagerController::class, 'pengaturanLimitEdit'])
        ->name('manager.pengaturan-limit.edit');
    Route::post('/manager/pengaturan-limit/edit/{id}', [ManagerController::class, 'pengaturanLimitUpdate'])
        ->name('manager.pengaturan-limit.update');

    // Export endpoints
    Route::get('/manager/export/stok-masuk', [ManagerController::class, 'exportStokMasuk'])
        ->name('manager.export.stok-masuk');
    Route::get('/manager/export/update-stok', [ManagerController::class, 'exportUpdateStok'])
        ->name('manager.export.update-stok');
    Route::get('/manager/export/update-stok-pdf', [ManagerController::class, 'exportUpdateStokPdf'])
        ->name('manager.export.update-stok-pdf');
    Route::get('/manager/export/daily-clean', [ManagerController::class, 'exportDailyClean'])
        ->name('manager.export.daily-clean');
    Route::get('/manager/export/token-listrik', [ManagerController::class, 'exportTokenListrik'])
        ->name('manager.export.token-listrik');
    Route::get('/manager/laporan/export', [ManagerController::class, 'laporanExport'])
        ->name('manager.laporan.export');
    Route::get('/manager/forecast/export-excel', [ManagerController::class, 'forecastExportExcel'])
        ->name('manager.forecast.export-excel');
    Route::get('/manager/forecast/export-pdf', [ManagerController::class, 'forecastExportPdf'])
        ->name('manager.forecast.export-pdf');

    // Daily Clean detail (JSON)
    Route::get('/manager/riwayat/daily-clean/detail/{id}', [ManagerController::class, 'dailyCleanDetail'])
        ->name('manager.riwayat.daily-clean.detail');

    // Token Listrik delete (single)
    Route::delete('/manager/riwayat/token-listrik/hapus/{id}', [ManagerController::class, 'tokenListrikDestroy'])
        ->name('manager.token-listrik.delete');

    // Token Listrik bulk delete
    Route::post('/manager/riwayat/token-listrik/hapus-massal', [ManagerController::class, 'tokenListrikBulkDelete'])
        ->name('manager.token-listrik.bulk-delete');

// Token Listrik detail page (full page, bukan modal)
    Route::get('/manager/riwayat/token-listrik/detail/{id}', [ManagerController::class, 'tokenListrikDetail'])
        ->name('manager.token-listrik.detail');

    // Daily Clean detail page (full page, bukan modal)
    Route::get('/manager/riwayat/daily-clean/view/{id}', [ManagerController::class, 'dailyCleanDetailPage'])
        ->name('manager.daily-clean.detail');

    // Daily Clean delete (single)
    Route::delete('/manager/riwayat/daily-clean/hapus/{id}', [ManagerController::class, 'dailyCleanDestroy'])
        ->name('manager.riwayat.daily-clean.delete');

    // Daily Clean bulk delete
    Route::post('/manager/riwayat/daily-clean/hapus-massal', [ManagerController::class, 'dailyCleanBulkDelete'])
        ->name('manager.riwayat.daily-clean.bulk-delete');

    // Edit Akun Saya (Update Profile)
    Route::post('/manager/profile/update', [ManagerController::class, 'updateProfile'])
        ->name('manager.profile.update');
});
