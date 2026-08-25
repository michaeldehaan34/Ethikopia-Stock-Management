<?php

namespace App\Services;

use App\Models\Bahan;
use App\Models\StokMasuk;
use App\Models\UpdateStok;
use Illuminate\Database\Eloquent\Builder;

/**
 * Port dari modul Flask:
 *  - modules/update_stok_reader.py (Single Source of Truth)
 *  - modules/manager_dashboard.py
 *  - modules/history.py
 *  - modules/forecast.py
 *  - modules/bahan_limit.py
 *
 * Semua statistik dihitung secara dinamis dari tabel update_stok /
 * stok_masuk via Eloquent (query dioptimasi: hanya kolom aktif + latest row).
 */
class StockAnalytics
{
    public const DEFAULT_LIMIT_HABIS = 0.0;
    public const DEFAULT_LIMIT_TIPIS = 2.0;

    /**
     * Map kode bahan aktif -> [limit_habis, limit_tipis] (dari relasi bahan_limit).
     *
     * Menggunakan relasi Eloquent Bahan::limit() (eager-loaded) sebagai
     * pengganti raw DB join.
     */
    public static function limitMap(): array
    {
        $bahanList = Bahan::with('limit')
            ->where('is_active', 1)
            ->select('id', 'kode')
            ->get();

        $map = [];
        foreach ($bahanList as $b) {
            $map[$b->kode] = [
                $b->limit?->limit_habis ?? self::DEFAULT_LIMIT_HABIS,
                $b->limit?->limit_tipis ?? self::DEFAULT_LIMIT_TIPIS,
            ];
        }

        return $map;
    }

    /**
     * Map kode bahan aktif -> nama bahan (dari Master Barang).
     */
    public static function keyToLabel(): array
    {
        $labels = Bahan::activeItems();
        $keyToLabel = [];
        foreach ($labels as $l) {
            $keyToLabel[$l['kode']] = $l['nama'];
        }

        return $keyToLabel;
    }

    /**
     * Map kode bahan aktif -> record bahan lengkap (dari Master Barang).
     */
    public static function activeMap(): array
    {
        $bahanMap = Bahan::activeItems();
        $map = [];
        foreach ($bahanMap as $b) {
            $map[$b['kode']] = $b;
        }

        return $map;
    }

    /**
     * Baca seluruh baris update_stok (hanya kolom bahan aktif) sekali saja.
     */
    /**
     * Kode bahan aktif yang BENAR-BENAR memiliki kolom di tabel update_stok.
     *
     * Pencegahan QueryException: kolom transaksi disinkronkan lewat
     * MasterBahanController (ALTER TABLE), namun bisa saja tidak sinkron
     * (mis. penambahan bahan gagal saat ALTER). Memilih kolom yang tidak
     * ada akan memicu "SQLSTATE[42S22]: Column not found" (QueryException)
     * sehingga dashboard kosong/error. Kita intercept hanya kolom yang
     * sungguh ada, tanpa mengubah desain maupun menghapus widget apa pun.
     *
     * @return array<int, string>
     */
    public static function existingUpdateStokKeys(): array
    {
        $active = Bahan::activeKeys();
        if (empty($active)) {
            return [];
        }

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('update_stok');
        $base = ['id', 'tanggal', 'shift', 'barista', 'created_at'];
        $bahanCols = array_diff($columns, $base);

        // Hanya kode aktif yang memiliki kolom nyata di update_stok.
        return array_values(array_intersect($active, $bahanCols));
    }

    public static function readUpdateStok(): array
    {
        $keys = self::existingUpdateStokKeys();
        if (empty($keys)) {
            return ['has_data' => false, 'item_keys' => [], 'rows' => [], 'last_row' => null];
        }

        $select = array_merge(['id', 'tanggal', 'shift', 'barista'], $keys);
        $rawRows = UpdateStok::query()->select($select)->orderBy('tanggal')->orderBy('id')->get();

        $rows = [];
        foreach ($rawRows as $rec) {
            $values = [];
            foreach ($keys as $k) {
                $values[$k] = $rec->$k;
            }
            $rows[] = [
                'tanggal' => $rec->tanggal ? $rec->tanggal->format('Y-m-d') : '',
                'shift' => $rec->shift ?: '-',
                'barista' => $rec->barista ?: '-',
                'values' => $values,
            ];
        }

        $lastRaw = UpdateStok::query()->select($select)->orderByDesc('tanggal')->orderByDesc('id')->first();
        $lastRow = null;
        if ($lastRaw) {
            $lastValues = [];
            foreach ($keys as $k) {
                $lastValues[$k] = $lastRaw->$k;
            }
            $lastRow = [
                'tanggal' => $lastRaw->tanggal ? $lastRaw->tanggal->format('Y-m-d') : '',
                'shift' => $lastRaw->shift ?: '-',
                'barista' => $lastRaw->barista ?: '-',
                'values' => $lastValues,
            ];
        }

        return [
            'has_data' => $rawRows->isNotEmpty(),
            'item_keys' => $keys,
            'rows' => $rows,
            'last_row' => $lastRow,
        ];
    }

    public static function classify($val, float $limitHabis, float $limitTipis): string
    {
        $v = self::toFloat($val);
        if ($v === null || $v <= $limitHabis) {
            return 'habis';
        }
        if ($v <= $limitTipis) {
            return 'tipis';
        }

        return 'aman';
    }

    public static function toFloat($val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        $s = str_replace(',', '.', (string) $val);
        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    public static function formatNumber($v): string
    {
        if ($v === null || $v === '') {
            return '-';
        }
        $f = (float) $v;
        if ($f == (int) $f) {
            return (string) (int) $f;
        }

        return rtrim(rtrim(sprintf('%g', $f), '0'), '.');
    }

    public static function displayDate(string $iso): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $iso)->format('d-m-Y');
        } catch (\Throwable $e) {
            return $iso;
        }
    }

    /**
     * Ringkasan status stok dari stok terakhir (Dashboard Analytics).
     */
    public static function summaryStats(array $data): array
    {
        $summary = ['aman' => 0, 'tipis' => 0, 'habis' => 0];
        $last = $data['last_row'] ?? null;
        if (! $last) {
            return $summary;
        }
        $limitMap = self::limitMap();
        foreach ($data['item_keys'] as $key) {
            [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
            $summary[self::classify($last['values'][$key] ?? null, $lh, $lt)]++;
        }

        return $summary;
    }

    public static function topHabis(array $data, ?array $limitMap = null, ?array $keyToLabel = null, int $limit = 10): array
    {
        $counter = [];
        $limitMap = $limitMap ?? self::limitMap();
        $keyToLabel = $keyToLabel ?? self::keyToLabel();
        foreach ($data['rows'] as $row) {
            foreach ($data['item_keys'] as $key) {
                [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
                if (self::classify($row['values'][$key] ?? null, $lh, $lt) === 'habis') {
                    $label = $keyToLabel[$key] ?? $key;
                    $counter[$label] = ($counter[$label] ?? 0) + 1;
                }
            }
        }
        arsort($counter);

        $result = [];
        $rank = 1;
        foreach ($counter as $name => $cnt) {
            if ($rank > $limit) {
                break;
            }
            $result[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        return $result;
    }

    public static function topTipis(array $data, ?array $limitMap = null, ?array $keyToLabel = null, int $limit = 10): array
    {
        $counter = [];
        $limitMap = $limitMap ?? self::limitMap();
        $keyToLabel = $keyToLabel ?? self::keyToLabel();
        foreach ($data['rows'] as $row) {
            foreach ($data['item_keys'] as $key) {
                [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
                if (self::classify($row['values'][$key] ?? null, $lh, $lt) === 'tipis') {
                    $label = $keyToLabel[$key] ?? $key;
                    $counter[$label] = ($counter[$label] ?? 0) + 1;
                }
            }
        }
        arsort($counter);

        $result = [];
        $rank = 1;
        foreach ($counter as $name => $cnt) {
            if ($rank > $limit) {
                break;
            }
            $result[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        return $result;
    }

    public static function aktivitasBarista(array $data): array
    {
        $counter = [];
        foreach ($data['rows'] as $row) {
            $nama = trim((string) $row['barista']);
            if ($nama !== '') {
                $counter[$nama] = ($counter[$nama] ?? 0) + 1;
            }
        }
        arsort($counter);

        $result = [];
        $no = 1;
        foreach ($counter as $name => $cnt) {
            $result[] = ['no' => $no, 'nama_barista' => $name, 'jumlah' => $cnt];
            $no++;
        }

        return $result;
    }

    public static function getCurrentStocks(): array
    {
        $keys = Bahan::activeKeys();
        
        $updateCols = \Illuminate\Support\Facades\Schema::getColumnListing('update_stok');
        $masukCols = \Illuminate\Support\Facades\Schema::getColumnListing('stok_masuk');
        
        $validKeys = [];
        foreach ($keys as $k) {
            if (in_array($k, $updateCols) && in_array($k, $masukCols)) {
                $validKeys[] = $k;
            }
        }
        
        if (empty($validKeys)) {
            return [];
        }

        $updates = UpdateStok::query()
            ->select(array_merge(['id', 'created_at'], $validKeys))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();
            
        $masuks = StokMasuk::query()
            ->select(array_merge(['id', 'created_at'], $validKeys))
            ->get();

        $bahanMap = self::activeMap();
        $limitMap = self::limitMap();
        
        $currentStocks = [];
        foreach ($validKeys as $key) {
            $latestUpdateVal = 0.0;
            $latestUpdateDate = null;
            
            foreach ($updates as $u) {
                if ($u->$key !== null && $u->$key !== '') {
                    $latestUpdateVal = self::toFloat($u->$key) ?? 0.0;
                    $latestUpdateDate = $u->created_at;
                    break;
                }
            }
            
            $sumMasuk = 0.0;
            foreach ($masuks as $m) {
                if ($m->$key !== null && $m->$key !== '') {
                    if ($latestUpdateDate === null || ($m->created_at && $m->created_at > $latestUpdateDate)) {
                        $sumMasuk += self::toFloat($m->$key) ?? 0.0;
                    }
                }
            }
            
            $actual = $latestUpdateVal + $sumMasuk;
            
            [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
            $status = self::classify($actual, $lh, $lt);
            
            $currentStocks[$key] = [
                'kode' => $key,
                'nama' => $bahanMap[$key]['nama'] ?? $key,
                'stok' => self::formatNumber($actual),
                'satuan' => $bahanMap[$key]['satuan'] ?? 'pcs',
                'status' => $status,
                'raw_stok' => $actual
            ];
        }
        
        return $currentStocks;
    }

    /**
     * Ringkas seluruh metrik Dashboard Analytics dalam SATU pass
     * (summary + top habis + top tipis + aktivitas barista) dengan hanya
     * SATU pemanggilan limitMap() dan SATU activeItems().
     * Menggantikan panggilan berulang di DashboardController yang sebelumnya
     * men-query DB berulang kali (limitMap 4x, activeItems 2x).
     */
    public static function dashboard(): array
    {
        $data = self::readUpdateStok();
        $currentStocks = self::getCurrentStocks();
        $hasAnyData = $data['has_data'] || !empty($currentStocks);

        if (! $hasAnyData) {
            return [
                'has_data' => false,
                'bahan_aman' => 0,
                'bahan_tipis' => 0,
                'bahan_habis' => 0,
                'top_barang_habis' => [],
                'top_barang_tipis' => [],
                'top_aktivitas_barista' => [],
                'stok_saat_ini' => [],
            ];
        }

        $limitMap = self::limitMap();
        $labels = Bahan::activeItems();
        $keyToLabel = [];
        foreach ($labels as $l) {
            $keyToLabel[$l['kode']] = $l['nama'];
        }

        // Ringkasan dari STOK SAAT INI (menggunakan stok aktual terbaru)
        $summary = ['aman' => 0, 'tipis' => 0, 'habis' => 0];
        foreach ($currentStocks as $item) {
            $summary[$item['status']]++;
        }

        // Satu pass untuk seluruh counter.
        $habisCounter = [];
        $tipisCounter = [];
        $aktivitasCounter = [];
        foreach ($data['rows'] as $row) {
            foreach ($data['item_keys'] as $key) {
                [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
                $status = self::classify($row['values'][$key] ?? null, $lh, $lt);
                $label = $keyToLabel[$key] ?? $key;
                if ($status === 'habis') {
                    $habisCounter[$label] = ($habisCounter[$label] ?? 0) + 1;
                } elseif ($status === 'tipis') {
                    $tipisCounter[$label] = ($tipisCounter[$label] ?? 0) + 1;
                }
            }
            $nama = trim((string) $row['barista']);
            if ($nama !== '') {
                $aktivitasCounter[$nama] = ($aktivitasCounter[$nama] ?? 0) + 1;
            }
        }

        arsort($habisCounter);
        arsort($tipisCounter);
        arsort($aktivitasCounter);

        $topHabis = [];
        $rank = 1;
        foreach ($habisCounter as $name => $cnt) {
            $topHabis[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        $topTipis = [];
        $rank = 1;
        foreach ($tipisCounter as $name => $cnt) {
            $topTipis[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        $topAktivitas = [];
        $no = 1;
        foreach ($aktivitasCounter as $name => $cnt) {
            $topAktivitas[] = ['no' => $no, 'nama_barista' => $name, 'jumlah' => $cnt];
            $no++;
        }

        return [
            'has_data' => true,
            'bahan_aman' => $summary['aman'],
            'bahan_tipis' => $summary['tipis'],
            'bahan_habis' => $summary['habis'],
            'top_barang_habis' => $topHabis,
            'top_barang_tipis' => $topTipis,
            'top_aktivitas_barista' => $topAktivitas,
            'stok_saat_ini' => array_values($currentStocks),
        ];
    }

    /**
     * Forecast kebutuhan & estimasi pembelian untuk periode terpilih.
     */
    public static function forecast(?string $tanggalAwal = null, ?string $tanggalAkhir = null, ?array $data = null, ?array $limitMap = null): array
    {
        $data = $data ?? self::readUpdateStok();
        $limitMap = $limitMap ?? self::limitMap();
        $result = [
            'has_data' => $data['has_data'],
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'periode_valid' => false,
            'jumlah_hari' => 0,
            'items' => [],
            'items_tree' => [],
            'total_kebutuhan' => 0,
            'total_estimasi_pembelian' => 0,
        ];

        if (! ($tanggalAwal && $tanggalAkhir)) {
            // Kedua tanggal wajib diisi untuk menghitung forecast.
            return $result;
        }
        if (! is_valid_date($tanggalAwal) || ! is_valid_date($tanggalAkhir)) {
            // Tanggal tidak valid (format selain Y-m-d / tanggal tidak ada).
            return $result;
        }
        if ($tanggalAwal > $tanggalAkhir) {
            // Tanggal awal melebihi tanggal akhir.
            return $result;
        }

        try {
            $d1 = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalAwal);
            $d2 = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalAkhir);
            $result['jumlah_hari'] = $d1->diffInDays($d2) + 1;
        } catch (\Throwable $e) {
            return $result;
        }

        $rows = array_filter($data['rows'], fn ($r) => $tanggalAwal <= $r['tanggal'] && $r['tanggal'] <= $tanggalAkhir);
        usort($rows, fn ($a, $b) => $a['tanggal'] <=> $b['tanggal']);

        $result['periode_valid'] = true;
        $current = $data['last_row'];

        $awalRaw = UpdateStok::query()->whereDate('tanggal', '<', $tanggalAwal)->orderByDesc('tanggal')->orderByDesc('id')->first();
        $stokMasukRows = StokMasuk::query()->whereDate('tanggal', '>=', $tanggalAwal)->whereDate('tanggal', '<=', $tanggalAkhir)->get();

        $totalKebutuhan = 0.0;
        $totalPembelian = 0.0;
        $labelToGroup = self::labelToGroup();

        foreach ($data['item_keys'] as $key) {
            [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];

            // 1. Stok Awal
            $awalVal = $awalRaw ? (self::toFloat($awalRaw->$key) ?? 0.0) : 0.0;
            
            // 2. Stok Masuk
            $masukVal = 0.0;
            foreach ($stokMasukRows as $sm) {
                $masukVal += self::toFloat($sm->$key) ?? 0.0;
            }
            
            // 3. Stok Akhir (last row in the period, or Awal if no updates)
            $akhirVal = $awalVal;
            if (!empty($rows)) {
                $lastRowInPeriod = end($rows);
                $akhirVal = self::toFloat($lastRowInPeriod['values'][$key] ?? null) ?? 0.0;
            }

            // Formula: Stok Awal + Stok Masuk - Stok Akhir
            $consumption = $awalVal + $masukVal - $akhirVal;

            $kebutuhan = round(max(0.0, $consumption), 2);
            $cur = $current ? self::toFloat($current['values'][$key] ?? null) : null;
            if ($cur === null) {
                $cur = 0.0;
            }
            $estimasi = round(max(0.0, $kebutuhan - $cur), 2);
            $status = $estimasi > 0 ? 'perlu_dibeli' : 'aman';

            $totalKebutuhan += $kebutuhan;
            $totalPembelian += $estimasi;

            $label = $labelToGroup[$key]['label'] ?? $key;
            $result['items'][] = [
                'nama_barang' => $label,
                'stok_sekarang' => $cur,
                'kebutuhan' => $kebutuhan,
                'estimasi_pembelian' => $estimasi,
                'status' => $status,
                'limit_habis' => $lh,
                'limit_tipis' => $lt,
                'kode' => $key,
            ];
        }

        $result['total_kebutuhan'] = round($totalKebutuhan, 2);
        $result['total_estimasi_pembelian'] = round($totalPembelian, 2);

        // Susun hierarki Kategori -> Kelompok -> Barang
        $tree = [];
        foreach ($result['items'] as $it) {
            $g = $labelToGroup[$it['kode']] ?? ['kategori' => 'Lainnya', 'kelompok' => 'Lainnya'];
            $kat = $g['kategori'];
            $kel = $g['kelompok'];
            $tree[$kat][$kel][] = $it;
        }
        $orderKat = ['Bahan Baku Bar', 'Bahan Baku Kitchen', 'Equipment'];
        $itemsTree = [];
        foreach ($orderKat as $kat) {
            if (isset($tree[$kat])) {
                $itemsTree[] = ['kategori' => $kat, 'kelompok_list' => self::buildKelompok($tree[$kat])];
                unset($tree[$kat]);
            }
        }
        foreach ($tree as $kat => $kelDict) {
            $itemsTree[] = ['kategori' => $kat, 'kelompok_list' => self::buildKelompok($kelDict)];
        }
        $result['items_tree'] = $itemsTree;

        return $result;
    }

    private static function buildKelompok(array $kelDict): array
    {
        $list = [];
        foreach ($kelDict as $kel => $items) {
            $list[] = ['kelompok' => $kel, 'items' => $items];
        }

        return $list;
    }

    /**
     * Map kode bahan -> [kategori, kelompok, label].
     */
    public static function labelToGroup(): array
    {
        $rows = Bahan::select('kode', 'nama', 'kategori', 'kelompok')->get();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->kode] = [
                'kategori' => $r->kategori ?: 'Lainnya',
                'kelompok' => $r->kelompok ?: 'Lainnya',
                'label' => $r->nama,
            ];
        }

        return $map;
    }

    /**
     * Riwayat Stok Masuk yang dikelompokkan per transaksi (untuk tabel + detail).
     */
    public static function groupedStokMasuk(?string $tglAwal, ?string $tglAkhir, ?string $shift, ?string $barista, ?string $barang): array
    {
        $keys = Bahan::activeKeys();

        $q = StokMasuk::query()->select(array_merge(['id', 'tanggal', 'shift', 'barista', 'created_at'], $keys));
        if ($tglAwal) {
            $q->where('tanggal', '>=', $tglAwal);
        }
        if ($tglAkhir) {
            $q->where('tanggal', '<=', $tglAkhir);
        }
        if ($shift) {
            $q->where('shift', $shift);
        }
        if ($barista) {
            $q->where('barista', 'like', '%'.$barista.'%');
        }
        self::applyBarangNameFilter($q, $barang);
        $raw = $q->orderByDesc('tanggal')->orderByDesc('id')->get();

        $transactions = [];
        foreach ($raw as $rec) {
            // Item diambil via relasi Eloquent ke Master Barang (StokMasuk::bahan()),
            // sehingga nama, satuan, kelompok, dan kategori otomatis mengikuti
            // tabel bahan (tidak ada data hardcode).
            $items = [];
            foreach ($rec->itemsFromMaster() as $it) {
                $items[] = [
                    'label' => $it['nama'],
                    'jumlah' => self::formatNumber($it['jumlah']),
                    'satuan' => $it['satuan'],
                    'kelompok' => $it['kelompok'],
                    'kategori' => $it['kategori'],
                    'catatan' => '-',
                ];
            }
            $tanggalIso = $rec->tanggal ? $rec->tanggal->format('Y-m-d') : '';
            $jam = $rec->created_at ? $rec->created_at->format('H:i') : '-';
            $transactions[] = [
                'id' => $rec->id,
                'tanggal' => $tanggalIso,
                'tanggal_display' => self::displayDate($tanggalIso),
                'jam' => $jam,
                'shift' => $rec->shift ?: '-',
                'barista' => $rec->barista ?: '-',
                'jumlah_item' => count($items),
                'items' => $items,
            ];
        }

        return $transactions;
    }

    /**
     * Terapkan filter pencarian Nama Barang (partial, case-insensitive)
     * langsung ke query Eloquent, meniru modules/repository.py ->
     * build_item_name_clause() pada Flask.
     *
     * Karena nama barang disimpan sebagai NAMA KOLOM (bukan nilai), kita
     * mencocokkan keyword terhadap daftar (kode, nama) bahan aktif, lalu
     * hanya mengikutkan transaksi yang kolom bersangkutan TERISI.
     *
     * Jika keyword tidak cocok dengan nama bahan manapun -> hasil kosong.
     */
    public static function applyBarangNameFilter(Builder $query, ?string $barang): void
    {
        if (! $barang || trim($barang) === '') {
            return;
        }

        $kw = strtolower(trim($barang));
        $matchedKeys = [];
        foreach (Bahan::activeItems() as $item) {
            if (str_contains(strtolower((string) ($item['nama'] ?? '')), $kw)) {
                $matchedKeys[] = $item['kode'];
            }
        }

        if (empty($matchedKeys)) {
            // Keyword tidak cocok dengan nama bahan manapun -> hasil kosong.
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($q) use ($matchedKeys) {
            foreach ($matchedKeys as $k) {
                $q->orWhere(function ($qq) use ($k) {
                    $qq->whereNotNull($k)->where($k, '<>', '');
                });
            }
        });
    }

    public static function stokMasukStats(array $transactions): array
    {
        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');
        $monthAgo = now()->subDays(30)->format('Y-m-d');

        $stats = ['total' => count($transactions), 'today' => 0, 'week' => 0, 'month' => 0];
        foreach ($transactions as $t) {
            if ($t['tanggal'] === $today) {
                $stats['today']++;
            }
            if ($t['tanggal'] >= $weekAgo) {
                $stats['week']++;
            }
            if ($t['tanggal'] >= $monthAgo) {
                $stats['month']++;
            }
        }

        return $stats;
    }

    /**
     * Riwayat Update Stok (flat per transaksi) untuk tabel + detail + export.
     */
    public static function allUpdateStok(?string $barangKeyword = null): array
    {
        $keys = Bahan::activeKeys();
        $bahanMap = Bahan::activeItems();
        $map = [];
        foreach ($bahanMap as $b) {
            $map[$b['kode']] = $b;
        }

        $q = UpdateStok::query()->select(array_merge(['id', 'tanggal', 'shift', 'barista'], $keys));
        if ($barangKeyword) {
            $q->where(function ($query) use ($keys, $barangKeyword) {
                foreach ($keys as $k) {
                    $query->orWhere($k, 'like', '%'.$barangKeyword.'%');
                }
            });
        }
        $raw = $q->orderByDesc('tanggal')->orderByDesc('id')->get();

        $records = [];
        foreach ($raw as $rec) {
            $items = [];
            $filled = 0;
            foreach ($keys as $k) {
                $rawVal = $rec->$k;
                $b = $map[$k] ?? null;
                $label = $b['nama'] ?? $k;
                $isFilled = $rawVal !== null && $rawVal !== '';
                $status = self::classify($rawVal, self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS);
                if ($isFilled) {
                    $filled++;
                }
                $items[] = [
                    'label' => $label,
                    'value' => $isFilled ? $rawVal : '-',
                    'status' => $status,
                ];
            }
            $tanggalIso = $rec->tanggal ? $rec->tanggal->format('Y-m-d') : '';
            $records[] = [
                'id' => $rec->id,
                'tanggal' => $tanggalIso,
                'tanggal_display' => self::displayDate($tanggalIso),
                'shift' => $rec->shift ?: '-',
                'barista' => $rec->barista ?: '-',
                'jumlah_item' => $filled,
                'items' => $items,
            ];
        }

        return $records;
    }

    public static function updateStokStats(array $records): array
    {
        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');
        $monthAgo = now()->subDays(30)->format('Y-m-d');

        $stats = ['total' => count($records), 'today' => 0, 'week' => 0, 'month' => 0];
        foreach ($records as $r) {
            if ($r['tanggal'] === $today) {
                $stats['today']++;
            }
            if ($r['tanggal'] >= $weekAgo) {
                $stats['week']++;
            }
            if ($r['tanggal'] >= $monthAgo) {
                $stats['month']++;
            }
        }

        return $stats;
    }

    /**
     * Perbandingan stok dua tanggal bebas.
     */
    public static function comparisonForDates(?string $tglAwal, ?string $tglPembanding): array
    {
        $result = [
            'requested' => true,
            'has_data' => false,
            'tanggal_valid' => true,
            'tanggal_awal' => '-',
            'tanggal_pembanding' => '-',
            'items' => [],
        ];

        $tglAwalN = $tglAwal ?: null;
        $tglPembandingN = $tglPembanding ?: null;
        $result['tanggal_awal'] = $tglAwalN ? self::displayDate($tglAwalN) : '-';
        $result['tanggal_pembanding'] = $tglPembandingN ? self::displayDate($tglPembandingN) : '-';

        if ($tglAwalN && $tglPembandingN && $tglAwalN > $tglPembandingN) {
            $result['tanggal_valid'] = false;

            return $result;
        }

        $keys = Bahan::activeKeys();
        $bahanMap = Bahan::activeItems();
        $map = [];
        foreach ($bahanMap as $b) {
            $map[$b['kode']] = $b;
        }

        $rowAwal = UpdateStok::query()->select($keys)->where('tanggal', $tglAwalN)->orderByDesc('id')->first();
        $rowPembanding = UpdateStok::query()->select($keys)->where('tanggal', $tglPembandingN)->orderByDesc('id')->first();

        if (! $rowAwal || ! $rowPembanding) {
            return $result;
        }

        $result['has_data'] = true;
        foreach ($keys as $k) {
            $b = $map[$k] ?? null;
            $label = $b['nama'] ?? $k;
            $unit = $b['satuan'] ?? 'pcs';
            $vAwal = self::toFloat($rowAwal->$k);
            $vPembanding = self::toFloat($rowPembanding->$k);
            $awalVal = $vAwal ?? 0.0;
            $pembandingVal = $vPembanding ?? 0.0;
            $selisih = round($pembandingVal - $awalVal, 2);
            $status = $selisih > 0 ? 'bertambah' : ($selisih < 0 ? 'berkurang' : 'tetap');
            $result['items'][] = [
                'label' => $label,
                'unit' => $unit,
                'stok_awal' => self::formatNumber($vAwal),
                'stok_pembanding' => self::formatNumber($vPembanding),
                'selisih' => $selisih,
                'status' => $status,
            ];
        }

        return $result;
    }
}