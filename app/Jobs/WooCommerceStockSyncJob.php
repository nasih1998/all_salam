<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\product_warehouse;
use App\Models\WooCommerceLog;
use App\Models\WooCommerceSetting;
use App\Services\WooCommerce\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WooCommerceStockSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $progressKey;

    public function __construct(string $progressKey)
    {
        $this->progressKey = $progressKey;
    }

    public function handle(): void
    {
        $settings = WooCommerceSetting::first();
        if (!$settings) {
            $this->failJob('WooCommerce settings missing');
            return;
        }

        $client = new Client((string) $settings->store_url, (string) $settings->consumer_key, (string) $settings->consumer_secret);

        $total = (int) Product::whereNull('deleted_at')->whereNotNull('woocommerce_id')->count();
        $state = [
            'total_products'     => $total,
            'synced_products'    => 0,
            'failed_products'    => 0,
            'percentage'         => 0,
            'last_product_id'    => null,
            'last_ok'            => null,
            'started_at'         => now()->toDateTimeString(),
            'finished'           => false,
            'error'              => null,
        ];
        Cache::put($this->progressKey, $state, 3600);

        Product::whereNull('deleted_at')
            ->whereNotNull('woocommerce_id')
            ->orderBy('id')
            ->chunk(200, function ($products) use (&$state, $client) {
                foreach ($products as $product) {
                    $ok = true; $message = 'OK'; $statusCode = null; $body = null;

                    try {
                        // Skip services
                        if (($product->type ?? '') === 'is_service') {
                            // mark as processed without remote call
                        } else if ((int) ($product->is_variant ?? 0) === 1 || ($product->type ?? '') === 'is_variant') {
                            // Variable product: update all variations by SKU/option
                            $existingBySku = [];
                            $existingByOpt = [];
                            $page = 1; $per = 100;
                            while (true) {
                                $vres = $client->get('products/' . (int) $product->woocommerce_id . '/variations', ['page' => $page, 'per_page' => $per]);
                                if (!$vres->successful()) break;
                                $list = $vres->json();
                                if (empty($list)) break;
                                foreach ($list as $v) {
                                    $vid = (int) ($v['id'] ?? 0);
                                    if ($vid <= 0) continue;
                                    $sku = (string) ($v['sku'] ?? '');
                                    if ($sku !== '') $existingBySku[$sku] = $vid;
                                    $attrs = $v['attributes'] ?? [];
                                    if (is_array($attrs) && isset($attrs[0]['option'])) {
                                        $opt = (string) $attrs[0]['option'];
                                        if ($opt !== '') $existingByOpt[$opt] = $vid;
                                    }
                                }
                                if (count($list) < $per) break;
                                $page++;
                            }

                            $anyInStock = false;
                            $variants = \App\Models\ProductVariant::where('product_id', $product->id)
                                ->whereNull('deleted_at')
                                ->get();
                            foreach ($variants as $var) {
                                $name = trim((string) ($var->name ?? ''));
                                $sku = trim((string) ($var->code ?? ''));
                                if ($sku === '') $sku = $product->code ? ($product->code . '-' . ($name !== '' ? $name : $var->id)) : ('VAR-' . $var->id);
                                $qty = $this->computeVariantStockQuantity((int) $product->id, (int) $var->id);
                                $status = $qty > 0 ? 'instock' : 'outofstock';
                                if ($qty > 0) $anyInStock = true;
                                $payloadVar = [ 'manage_stock' => true, 'stock_quantity' => $qty, 'stock_status' => $status ];

                                // Attach main product image to variation (prefer WP media id)
                                $media = $this->resolveOrUploadWpMedia($product);
                                if ($media && isset($media['id'])) {
                                    $payloadVar['image'] = [ 'id' => (int) $media['id'] ];
                                } else {
                                    try {
                                        $imgName = (string) ($product->image ?? '');
                                        if ($imgName !== '' && strtolower($imgName) !== 'no-image.png') {
                                            $public = public_path('images/products/' . $imgName);
                                            if (is_file($public)) {
                                                $payloadVar['image'] = [ 'src' => asset('images/products/' . $imgName) ];
                                            }
                                        }
                                    } catch (\Throwable $e) {}
                                }

                                $target = $existingBySku[$sku] ?? ($existingByOpt[$name] ?? null);
                                if ($target) {
                                    $res = $client->put('products/' . (int) $product->woocommerce_id . '/variations/' . $target, $payloadVar);
                                    $ok = $ok && $res->successful();
                                    if (!$ok) { $statusCode = $res->status(); $body = $res->body(); }
                                } else {
                                    $createPayload = $payloadVar + [ 'sku' => $sku, 'attributes' => [[ 'name' => 'Variant', 'option' => $name !== '' ? $name : ('Variant ' . $var->id) ]] ];
                                    $res = $client->post('products/' . (int) $product->woocommerce_id . '/variations', $createPayload);
                                    $ok = $ok && $res->successful();
                                    if (!$ok) { $statusCode = $res->status(); $body = $res->body(); }
                                }
                            }

                            // Update parent stock status only
                            try { $client->put('products/' . (int) $product->woocommerce_id, [ 'manage_stock' => false, 'stock_status' => $anyInStock ? 'instock' : 'outofstock' ]); } catch (\Throwable $e) {}

                        } else if (($product->type ?? '') === 'is_combo') {
                            $qty = $this->computeComboStockQuantity((int) $product->id);
                            $status = $qty > 0 ? 'instock' : 'outofstock';
                            $payload = [ 'manage_stock' => true, 'stock_quantity' => $qty, 'stock_status' => $status ];
                            // Attach main product image (prefer WP media id)
                            $media = $this->resolveOrUploadWpMedia($product);
                            if ($media && isset($media['id'])) {
                                $payload['images'] = [[ 'id' => (int) $media['id'] ]];
                            } else {
                                try {
                                    $imgName = (string) ($product->image ?? '');
                                    if ($imgName !== '' && strtolower($imgName) !== 'no-image.png') {
                                        $public = public_path('images/products/' . $imgName);
                                        if (is_file($public)) {
                                            $payload['images'] = [[ 'src' => asset('images/products/' . $imgName) ]];
                                        }
                                    }
                                } catch (\Throwable $e) {}
                            }
                            $res = $client->put('products/' . (int) $product->woocommerce_id, $payload);
                            $ok = $res->successful();
                            if (!$ok) { $statusCode = $res->status(); $body = $res->body(); }
                        } else {
                            // Simple
                            $qty = $this->computeStockQuantity((int) $product->id);
                            $status = $qty > 0 ? 'instock' : 'outofstock';
                            $payload = [ 'manage_stock' => true, 'stock_quantity' => $qty, 'stock_status' => $status ];
                            // Attach main product image (prefer WP media id)
                            $media = $this->resolveOrUploadWpMedia($product);
                            if ($media && isset($media['id'])) {
                                $payload['images'] = [[ 'id' => (int) $media['id'] ]];
                            } else {
                                try {
                                    $imgName = (string) ($product->image ?? '');
                                    if ($imgName !== '' && strtolower($imgName) !== 'no-image.png') {
                                        $public = public_path('images/products/' . $imgName);
                                        if (is_file($public)) {
                                            $payload['images'] = [[ 'src' => asset('images/products/' . $imgName) ]];
                                        }
                                    }
                                } catch (\Throwable $e) {}
                            }
                            $res = $client->put('products/' . (int) $product->woocommerce_id, $payload);
                            $ok = $res->successful();
                            if (!$ok) { $statusCode = $res->status(); $body = $res->body(); }
                        }
                    } catch (\Throwable $e) {
                        $message = $e->getMessage();
                        $ok = false;
                    }

                    if ($ok) {
                        $state['synced_products']++;
                    } else {
                        $state['failed_products']++;
                        WooCommerceLog::create([
                            'action'  => 'stock.sync',
                            'level'   => 'error',
                            'message' => 'Stock sync failed',
                            'context' => [
                                'product_id' => $product->id,
                                'woocommerce_id' => (int) $product->woocommerce_id,
                                'status' => $statusCode,
                                'body'   => $body,
                                'error'  => $message,
                            ],
                        ]);
                    }

                    $processed = $state['synced_products'] + $state['failed_products'];
                    $state['percentage'] = $this->computePercentage($processed, $state['total_products']);
                    $state['last_product_id'] = (int) $product->id;
                    $state['last_ok'] = $ok;
                    Cache::put($this->progressKey, $state, 3600);
                }
            });

        $state['finished'] = true;
        $state['finished_at'] = now()->toDateTimeString();
        Cache::put($this->progressKey, $state, 3600);

        $settings->last_sync_at = now();
        $settings->save();

        WooCommerceLog::create([
            'action'  => 'stock.sync',
            'level'   => 'info',
            'message' => 'Stock sync completed',
            'context' => [
                'processed' => $state['synced_products'] + $state['failed_products'],
                'success'   => $state['synced_products'],
                'failed'    => $state['failed_products'],
            ],
        ]);
    }

    private function computeStockQuantity(int $productId): int
    {
        $sum = (float) product_warehouse::where('product_id', $productId)
            ->whereNull('deleted_at')
            ->sum('qte');
        $qty = (int) round($sum);
        return $qty < 0 ? 0 : $qty;
    }

    private function computeVariantStockQuantity(int $productId, int $variantId): int
    {
        $sum = (float) product_warehouse::where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->whereNull('deleted_at')
            ->sum('qte');
        $qty = (int) round($sum);
        return $qty < 0 ? 0 : $qty;
    }

    private function computeComboStockQuantity(int $productId): int
    {
        $components = \DB::table('combined_products')
            ->where('product_id', $productId)
            ->get(['combined_product_id', 'quantity']);
        if ($components->isEmpty()) return $this->computeStockQuantity($productId);
        $min = null;
        foreach ($components as $c) {
            $componentStock = $this->computeStockQuantity((int) $c->combined_product_id);
            $required = max(1.0, (float) $c->quantity);
            $possible = (int) floor($componentStock / $required);
            $min = is_null($min) ? $possible : min($min, $possible);
        }
        return max(0, (int) ($min ?? 0));
    }

    private function computePercentage(int $processed, int $total): int
    {
        if ($total <= 0) return 100;
        $p = (int) floor(($processed / $total) * 100);
        if ($p > 100) $p = 100;
        if ($p < 0) $p = 0;
        return $p;
    }

    private function failJob(string $message): void
    {
        $state = Cache::get($this->progressKey, []);
        $state['finished'] = true;
        $state['error'] = $message;
        $state['percentage'] = 100;
        Cache::put($this->progressKey, $state, 3600);

        WooCommerceLog::create([
            'action'  => 'stock.sync',
            'level'   => 'error',
            'message' => $message,
            'context' => [],
        ]);
    }

    private function resolveOrUploadWpMedia($product): ?array
    {
        try {
            $settings = WooCommerceSetting::first();
            if (!$settings) return null;
            $username = (string) ($settings->wp_username ?? '');
            $appPass  = (string) ($settings->wp_app_password ?? '');
            $baseUrl  = rtrim((string) ($settings->store_url ?? ''), '/');
            if ($username === '' || $appPass === '' || $baseUrl === '') return null;

            $imgName = (string) ($product->image ?? '');
            if ($imgName === '' || strtolower($imgName) === 'no-image.png') return null;
            $abs = public_path('images/products/' . $imgName);
            if (!is_file($abs)) return null;

            $filenameBase = pathinfo($imgName, PATHINFO_FILENAME);
            $mediaList = Http::timeout(30)
                ->retry(2, 500)
                ->withBasicAuth($username, $appPass)
                ->get($baseUrl . '/wp-json/wp/v2/media', [ 'search' => $filenameBase, 'per_page' => 50 ]);
            if ($mediaList->successful()) {
                $items = $mediaList->json();
                if (is_array($items)) {
                    foreach ($items as $m) {
                        $src = (string) ($m['source_url'] ?? '');
                        if ($src !== '' && (stripos($src, $imgName) !== false || stripos($src, $filenameBase) !== false)) {
                            $id = (int) ($m['id'] ?? 0);
                            if ($id > 0) return [ 'id' => $id, 'src' => $src ];
                        }
                    }
                }
            } else {
                try { WooCommerceLog::create(['action' => 'media.resolve', 'level' => 'error', 'message' => 'WP media search failed (job)', 'context' => ['status' => $mediaList->status(), 'body' => $mediaList->body()]]); } catch (\Throwable $e) {}
            }

            $mime = 'image/jpeg';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $det = finfo_file($finfo, $abs);
                    if ($det) $mime = $det;
                    finfo_close($finfo);
                }
            }

            $upload = Http::timeout(60)
                ->retry(2, 800)
                ->withBasicAuth($username, $appPass)
                ->attach('file', fopen($abs, 'r'), $imgName)
                ->withHeaders([
                    'Content-Disposition' => 'attachment; filename="' . $imgName . '"',
                ])
                ->post($baseUrl . '/wp-json/wp/v2/media');

            if ($upload->successful() || $upload->status() === 201) {
                $body = $upload->json();
                $id = (int) ($body['id'] ?? 0);
                $src = (string) ($body['source_url'] ?? '');
                if ($id > 0) return [ 'id' => $id, 'src' => $src ];
            } else {
                try { WooCommerceLog::create(['action' => 'media.upload', 'level' => 'error', 'message' => 'WP media upload failed (job)', 'context' => ['status' => $upload->status(), 'body' => $upload->body()]]); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {}
        return null;
    }
}


