<?php

namespace App\Console\Commands;

use App\Services\StockBackfillService;
use Illuminate\Console\Command;

class BackfillStockCommand extends Command
{
    protected $signature = 'stock:backfill';

    protected $description = 'Buat dan tautkan unit stok untuk transaksi aktif yang belum memiliki unit stok.';

    public function handle(StockBackfillService $backfill): int
    {
        $processed = $backfill->run();
        $this->info("Unit stok dibuat dan ditautkan: {$processed}.");

        return self::SUCCESS;
    }
}
