<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WooCommerceSetting;
use App\Services\WooCommerce\SyncService;
use App\Jobs\WooCommerceStockSyncJob;

class WooCommerceSync extends Command
{
    protected $signature = 'woocommerce:sync {--scope=products}';
    protected $description = 'Run WooCommerce synchronization (products|stock|orders|all)';

    public function handle(): int
    {
        $settings = WooCommerceSetting::first();
        if (!$settings) {
            $this->warn('WooCommerce is not configured.');
            return 0;
        }

        $sync = SyncService::fromSettings($settings);
        $scope = strtolower((string)$this->option('scope'));

        if ($scope === 'all') {
            $this->info('Running Products Push (POS → Woo)...');
            $res = $sync->pushProducts(false, function ($progress) {
                $this->line(json_encode(['label' => 'Products Push', 'progress' => $progress]));
            });
            $this->info('Products Push done: '.json_encode($res));

            $this->info('Running Stock Sync (POS → Woo)...');
            $sres = $sync->syncStock(function ($progress) {
                $this->line(json_encode(['label' => 'Stock Sync', 'progress' => $progress]));
            });
            $this->info('Stock Sync done: '.json_encode($sres));
        } elseif ($scope === 'products') {
            $this->info('Running Products Push (POS → Woo)...');
            $res = $sync->pushProducts(false, function ($progress) {
                $this->line(json_encode(['label' => 'Products Push', 'progress' => $progress]));
            });
            $this->info('Products Push done: '.json_encode($res));
        } elseif ($scope === 'stock') {
            $this->info('Running Stock Sync (POS → Woo)...');
            $sres = $sync->syncStock(function ($progress) {
                $this->line(json_encode(['label' => 'Stock Sync', 'progress' => $progress]));
            });
            $this->info('Stock Sync done: '.json_encode($sres));
        } elseif ($scope === 'orders') {
            $this->warn('Orders sync is disabled for one-way POS → Woo configuration.');
        } else {
            $this->error('Unknown scope. Use products|stock|orders|all');
            return 1;
        }

        $settings->last_sync_at = now();
        $settings->save();
        return 0;
    }
}


