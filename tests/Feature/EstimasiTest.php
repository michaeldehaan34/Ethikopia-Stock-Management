<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Bahan;
use App\Models\UpdateStok;
use App\Models\StokMasuk;
use App\Services\StockAnalytics;

class EstimasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(); // To populate master data Bahan
    }

    public function test_stok_saat_ini()
    {
        // Setup base data
        UpdateStok::create([
            'tanggal' => '2026-08-01', 
            'shift' => 'Pagi', 
            'barista' => 'Test', 
            'arabica' => 25, 
            'created_at' => '2026-08-01 10:00:00'
        ]);
        
        $stokA = StockAnalytics::getCurrentStocks();
        $valA = collect($stokA)->firstWhere('kode', 'arabica')['raw_stok'];
        $this->assertEquals(25, $valA, 'Case A');

        StokMasuk::create([
            'tanggal' => '2026-08-02', 
            'shift' => 'Pagi', 
            'barista' => 'Test', 
            'arabica' => 10, 
            'created_at' => '2026-08-02 10:00:00'
        ]);
        
        $stokB = StockAnalytics::getCurrentStocks();
        $valB = collect($stokB)->firstWhere('kode', 'arabica')['raw_stok'];
        $this->assertEquals(35, $valB, 'Case B');

        UpdateStok::create([
            'tanggal' => '2026-08-03', 
            'shift' => 'Pagi', 
            'barista' => 'Test', 
            'arabica' => 20, 
            'created_at' => '2026-08-03 10:00:00'
        ]);
        
        $stokC = StockAnalytics::getCurrentStocks();
        $valC = collect($stokC)->firstWhere('kode', 'arabica')['raw_stok'];
        $this->assertEquals(20, $valC, 'Case C');
    }

    public function test_estimasi_penggunaan()
    {
        $testUsage = function($startDate, $endDate, $awal, $masuk, $akhir) {
            UpdateStok::create([
                'tanggal' => date('Y-m-d', strtotime($startDate . ' -1 day')), 
                'shift' => 'Malam', 'barista' => 'Test', 'arabica' => $awal,
                'created_at' => date('Y-m-d H:i:s', strtotime($startDate . ' -1 day 23:59:00'))
            ]);
            
            if ($masuk > 0) {
                StokMasuk::create([
                    'tanggal' => $startDate, 'shift' => 'Pagi', 'barista' => 'Test', 'arabica' => $masuk,
                    'created_at' => date('Y-m-d H:i:s', strtotime($startDate . ' 08:00:00'))
                ]);
            }
            
            UpdateStok::create([
                'tanggal' => $endDate, 'shift' => 'Malam', 'barista' => 'Test', 'arabica' => $akhir,
                'created_at' => date('Y-m-d H:i:s', strtotime($endDate . ' 23:59:00'))
            ]);
            
            $forecast = StockAnalytics::forecast($startDate, $endDate);
            $kebutuhanVal = collect($forecast['items'])->firstWhere('kode', 'arabica')['kebutuhan'] ?? 0;
            return $kebutuhanVal;
        };

        // Test 1: Awal 20, Masuk 5, Akhir 18 -> 7
        $this->assertEquals(7, $testUsage('2026-09-01', '2026-09-01', 20, 5, 18), 'Test 1');
        
        // Test 2: Awal 20, Masuk 0, Akhir 15 -> 5
        $this->assertEquals(5, $testUsage('2026-09-02', '2026-09-02', 20, 0, 15), 'Test 2');
        
        // Test 3: Edge Case (Negative Usage) should now be clamped to 0
        $this->assertEquals(0, $testUsage('2026-09-03', '2026-09-03', 10, 0, 15), 'Test Negative');
    }

    public function test_forecast_logic()
    {
        // 1. Forecast = 20, Limit Tipis = 10 (diset di Config/Seeder default 2, jadi kita pakai 2 sbg limit tipis), Stok Saat Ini = 20
        // Karena kebutuhan 20 dan stok 20, Estimasi = 0, Status = aman.
        UpdateStok::create([
            'tanggal' => '2026-10-01', 'shift' => 'Pagi', 'barista' => 'Test', 'arabica' => 40,
            'created_at' => '2026-10-01 08:00:00'
        ]);
        UpdateStok::create([
            'tanggal' => '2026-10-02', 'shift' => 'Malam', 'barista' => 'Test', 'arabica' => 20,
            'created_at' => '2026-10-02 23:59:00'
        ]);
        
        $forecast = StockAnalytics::forecast('2026-10-02', '2026-10-02');
        $item = collect($forecast['items'])->firstWhere('kode', 'arabica');
        
        $this->assertEquals(20, $item['kebutuhan']);
        $this->assertEquals(0, $item['estimasi_pembelian']); // max(0, 20 - 20)
        $this->assertEquals('aman', $item['status']);
        
        // 2. Forecast = 20, Limit Tipis = 10, Stok Saat Ini = 5
        // Estimasi = max(0, 20 - 5) = 15. Status = perlu_dibeli.
        UpdateStok::create([
            'tanggal' => '2026-10-03', 'shift' => 'Pagi', 'barista' => 'Test', 'arabica' => 25,
            'created_at' => '2026-10-03 08:00:00'
        ]);
        UpdateStok::create([
            'tanggal' => '2026-10-04', 'shift' => 'Malam', 'barista' => 'Test', 'arabica' => 5,
            'created_at' => '2026-10-04 23:59:00'
        ]);
        
        $forecast2 = StockAnalytics::forecast('2026-10-04', '2026-10-04');
        $item2 = collect($forecast2['items'])->firstWhere('kode', 'arabica');
        
        $this->assertEquals(20, $item2['kebutuhan']);
        $this->assertEquals(15, $item2['estimasi_pembelian']); // max(0, 20 - 5)
        $this->assertEquals('perlu_dibeli', $item2['status']);

        // 3. Forecast = 20, Limit Tipis = 10, Stok Saat Ini = 35
        // Estimasi = max(0, 20 - 35) = 0. Status = aman.
        UpdateStok::create([
            'tanggal' => '2026-10-05', 'shift' => 'Pagi', 'barista' => 'Test', 'arabica' => 55,
            'created_at' => '2026-10-05 08:00:00'
        ]);
        UpdateStok::create([
            'tanggal' => '2026-10-06', 'shift' => 'Malam', 'barista' => 'Test', 'arabica' => 35,
            'created_at' => '2026-10-06 23:59:00'
        ]);
        
        $forecast3 = StockAnalytics::forecast('2026-10-06', '2026-10-06');
        $item3 = collect($forecast3['items'])->firstWhere('kode', 'arabica');
        
        $this->assertEquals(20, $item3['kebutuhan']);
        $this->assertEquals(0, $item3['estimasi_pembelian']); // max(0, 20 - 35)
        $this->assertEquals('aman', $item3['status']);
    }
}
