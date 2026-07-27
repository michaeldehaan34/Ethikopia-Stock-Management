<?php

namespace App\Http\Controllers;

use App\Http\Requests\StokMasukRequest;
use App\Models\Bahan;
use App\Models\StokMasuk;
use App\Services\StockAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Modul Riwayat Stok Masuk (migrasi dari Flask ke Laravel 12).
 *
 * Menampilkan seluruh riwayat stok masuk menggunakan Eloquent ORM
 * (model StokMasuk via StockAnalytics::groupedStokMasuk), dengan
 * pencarian (filter) dan pagination.
 *
 * Tidak ada operasi tambah/edit/hapus pada modul ini.
 */
class StokMasukController extends Controller
{
    /**
     * Halaman daftar / riwayat Stok Masuk.
     *
     * Data diambil via Eloquent ORM (StokMasuk) lalu dikelompokkan per
     * transaksi (satu baris = satu transaksi) sehingga kolom tampilan
     * persis meniru Flask: No, Tanggal, Shift, Barista, Jumlah Item, Aksi.
     */
    public function index(Request $request): View
    {
        $tglAwal = $request->input('tgl_awal');
        $tglAkhir = $request->input('tgl_akhir');
        $shift = $request->input('shift');
        $barista = $request->input('barista');
        $barang = $request->input('barang');

        // Ambil data menggunakan Eloquent ORM.
        $transactions = StockAnalytics::groupedStokMasuk(
            $tglAwal,
            $tglAkhir,
            $shift,
            $barista,
            $barang
        );
        $stats = StockAnalytics::stokMasukStats($transactions);

        // Baris tingkat transaksi (satu baris = satu transaksi) agar kolom
        // tampilan persis meniru Flask: No, Tanggal, Shift, Barista,
        // Jumlah Item, Aksi. Tabel sumber stok_masuk hanya menyimpan
        // tanggal, shift, barista + kolom bahan, sehingga tidak ada kolom
        // Supplier/Keterangan seperti pada modul Flask asli.
        $rows = [];
        foreach ($transactions as $t) {
            $rows[] = [
                'id' => $t['id'],
                'tanggal_display' => $t['tanggal_display'],
                'shift' => $t['shift'],
                'barista' => $t['barista'],
                'jumlah_item' => $t['jumlah_item'],
            ];
        }

        // Pagination (10 baris per halaman).
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $collection = Collection::make($rows);
        $paginator = new LengthAwarePaginator(
            $collection->forPage($currentPage, $perPage)->values(),
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('manager.riwayat-stok-masuk', [
            'title' => 'Riwayat Stok Masuk',
            'rows' => $paginator->items(),
            'paginator' => $paginator,
            'records' => array_values($transactions),
            'stats' => $stats,
            'filter_tgl_awal' => $tglAwal,
            'filter_tgl_akhir' => $tglAkhir,
            'filter_shift' => $shift,
            'filter_barista' => $barista,
            'filter_barang' => $barang,
            'shift_list' => shift_list(),
            'barang_suggestions' => Bahan::activeItems(),
        ]);
    }

    /**
     * Halaman detail Stok Masuk (full page info, bukan modal).
     */
    public function detail(int $id): View
    {
        $record = StokMasuk::findOrFail($id);
        $items = [];

        foreach (Bahan::activeKeys() as $kode) {
            $val = $record->{$kode};
            if ($val !== null && $val !== '' && (float)$val > 0) {
                $bahan = Bahan::where('kode', $kode)->first();
                $items[] = [
                    'kode' => $kode,
                    'nama' => $bahan?->nama ?? $kode,
                    'kelompok' => $bahan?->kelompok ?? '-',
                    'kategori' => $bahan?->kategori ?? '-',
                    'satuan' => $bahan?->satuan ?? '',
                    'nilai' => $val,
                ];
            }
        }

        return view('manager.stok-masuk.detail', [
            'title' => 'Detail Stok Masuk',
            'record' => $record,
            'items' => $items,
        ]);
    }

    /**
     * Halaman form tambah Stok Masuk.
     *
     * Dropdown barang, kategori, kelompok, dan satuan diambil dari Master
     * Barang (Bahan::groupedActiveTree) — tidak ada data hardcode.
     */
    public function create(): View
    {
        return view('manager.stok-masuk.create', [
            'title' => 'Tambah Stok Masuk',
            'bahan_tree' => Bahan::groupedActiveTree(),
            'shift_list' => shift_list(),
            'barista_name' => session('name') ?: session('username'),
            'default_data' => session('form_data', []),
        ]);
    }

    /**
     * Proses simpan Stok Masuk baru (validasi + Eloquent insert).
     *
     * Validasi diserahkan ke StokMasukRequest (homolog modul Flask
     * modules/stok_masuk.py -> validate_form).
     */
    public function store(StokMasukRequest $request): RedirectResponse
    {
        StokMasuk::create($request->validatedStokData());

        flash_success('Data stok masuk berhasil disimpan.');

        return redirect()->route('manager.stok-masuk.index');
    }

    /**
     * Halaman form edit Stok Masuk.
     *
     * Data transaksi diambil via Eloquent ORM (StokMasuk) lalu dipetakan ke
     * bentuk default_data agar accordion Master Barang terisi.
     */
    public function edit(int $id): View
    {
        $record = StokMasuk::findOrFail($id);

        $defaultData = [
            'tanggal' => $record->tanggal?->format('Y-m-d'),
            'shift' => $record->shift,
            'barista' => $record->barista,
        ];

        // Isi nilai tiap kolom bahan dari atribut model (nama kolom = kode).
        foreach (Bahan::activeKeys() as $kode) {
            $defaultData[$kode] = $record->{$kode};
        }

        return view('manager.stok-masuk.edit', [
            'title' => 'Edit Stok Masuk',
            'id' => $id,
            'bahan_tree' => Bahan::groupedActiveTree(),
            'shift_list' => shift_list(),
            'barista_name' => $record->barista,
            'default_data' => $defaultData,
        ]);
    }

    /**
     * Proses update Stok Masuk (validasi + Eloquent update).
     */
    public function update(StokMasukRequest $request, int $id): RedirectResponse
    {
        $record = StokMasuk::findOrFail($id);

        $record->update($request->validatedStokData());

        flash_success('Data stok masuk berhasil diperbarui.');

        return redirect()->route('manager.stok-masuk.index');
    }

    /**
     * Proses hapus Stok Masuk (Eloquent delete dalam transaksi DB).
     *
     * Satu baris stok_masuk = satu transaksi, sehingga penghapusan cukup
     * menghapus 1 record. Transaksi digunakan untuk menjaga konsistensi
     * (homolog pola delete pada modul lain di Laravel).
     */
    public function destroy(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $record = StokMasuk::findOrFail($id);
            $record->delete();
        });

        flash_success('Data stok masuk berhasil dihapus.');

        return redirect()->route('manager.stok-masuk.index');
    }
}
