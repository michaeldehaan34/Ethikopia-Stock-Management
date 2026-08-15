<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UpdateStok;
use App\Models\StokMasuk;
use App\Services\StockAnalytics;

// We will test the basic math: Awal + Masuk - Akhir = Usage

// Let's take 'arabica' as the valid key
$validKeys = ['arabica'];

$date = '2026-08-14';
$awal = StockAnalytics::getActualStocksAtDate('2026-08-13', $validKeys);
$masuk = StockAnalytics::getMasukOnDate('2026-08-14', $validKeys);
$akhir = StockAnalytics::getActualStocksAtDate('2026-08-14', $validKeys);

echo "Tgl: 2026-08-14\n";
echo "Awal: " . ($awal['arabica'] ?? 0) . "\n";
echo "Masuk: " . ($masuk['arabica'] ?? 0) . "\n";
echo "Akhir: " . ($akhir['arabica'] ?? 0) . "\n";
$usage = ($awal['arabica'] ?? 0) + ($masuk['arabica'] ?? 0) - ($akhir['arabica'] ?? 0);
echo "Usage: " . $usage . "\n";
