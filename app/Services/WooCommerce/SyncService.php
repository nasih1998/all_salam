<?php

namespace App\Services\WooCommerce;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category as PosCategory;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\WooCommerceLog;
use App\Models\WooCommerceSetting;
use App\Models\product_warehouse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class SyncService
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function fromSettings(WooCommerceSetting $settings): self
    {
        return new self(new Client($settings->store_url, $settings->consumer_key, $settings->consumer_secret));
    }

    public function testConnection(): array
    {
        $res = $this->client->get('system_status');
        if ($res->successful()) {
            return ['ok' => true, 'data' => $res->json()];
        }
        return ['ok' => false, 'status' => $res->status(), 'error' => $res->body()];
    }

    public function syncProducts(callable $progress = null): array
    {
        $synced = 0; $errors = 0; $page = 1; $perPage = 50;
        while (true) {
            $res = $this->client->get('products', ['page' => $page, 'per_page' => $perPage, 'status' => 'any']);
            if (!$res->successful()) {
                $this->log('products.sync', 'error', 'Failed fetching page', ['page' => $page, 'status' => $res->status(), 'body' => $res->body()]);
                break;
            }
            $items = $res->json();
            if (empty($items)) break;
            foreach ($items as $p) {
                try {
                    DB::transaction(function () use ($p) {
                        $code = (string)($p['sku'] ?? ("WC-" . $p['id']));
                        $product = Product::firstOrNew(['code' => $code]);
                        // Mark as synced with WooCommerce by storing its remote ID
                        if (isset($p['id'])) {
                            $product->woocommerce_id = (int) $p['id'];
                        }
                        $product->name = $p['name'] ?? $product->name;
                        // Woo price can be string, handle sale_price/regular_price fallback
                        $price = $p['price'] ?? ($p['sale_price'] ?? ($p['regular_price'] ?? null));
                        $product->price = $price !== null ? (float)$price : ($product->price ?? 0);
                        if (!$product->exists) {
                            $product->Type_barcode = 'CODE128';
                            $product->unit_id = $product->unit_id ?? 1;
                            $product->unit_sale_id = $product->unit_sale_id ?? 1;
                            $product->unit_purchase_id = $product->unit_purchase_id ?? 1;
                            $product->category_id = $product->category_id ?? 1;
                            $product->stock_alert = $product->stock_alert ?? 0;
                            $product->is_active = 1;
                        }
                        $product->save();
                    }, 3);
                    $synced++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->log('products.sync', 'error', $e->getMessage(), ['trace' => $e->getTraceAsString(), 'product' => $p]);
                }
            }
            if ($progress) $progress(['page' => $page, 'synced' => $synced]);
            if (count($items) < $perPage) break;
            $page++;
        }
        $this->log('products.sync', 'info', 'Products sync completed', ['synced' => $synced, 'errors' => $errors]);
        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Push local POS products to WooCommerce (POS → WooCommerce).
     * If $onlyUnsynced = true, only products without woocommerce_id are pushed.
     * Minimal safe payload: name, type, sku, regular_price, status.
     */
    public function pushProducts(bool $onlyUnsynced = false, callable $progress = null): array
    {
        $created = 0; $updated = 0; $errors = 0; $processed = 0;

        $query = Product::whereNull('deleted_at');
        if ($onlyUnsynced) {
            $query->whereNull('woocommerce_id');
        }

        $query->orderBy('id')->chunk(100, function ($products) use (&$created, &$updated, &$errors, &$processed, $progress) {
            foreach ($products as $product) {
                try {
                    // If we don't have a mapping yet, try to link by SKU to avoid duplicates when switching stores
                    if (empty($product->woocommerce_id)) {
                        $sku = (string) ($product->code ?? '');
                        if ($sku !== '') {
                            try {
                                $findRes = $this->client->get('products', ['sku' => $sku]);
                                if ($findRes->successful()) {
                                    $list = $findRes->json();
                                    if (is_array($list) && count($list) > 0 && isset($list[0]['id'])) {
                                        $product->woocommerce_id = (int) $list[0]['id'];
                                        $product->save();
                                        $this->log('products.push', 'info', 'Linked existing Woo product by SKU', [
                                            'product_id' => $product->id,
                                            'sku' => $sku,
                                            'woocommerce_id' => $product->woocommerce_id,
                                        ]);
                                    }
                                }
                            } catch (Throwable $e) {
                                $this->log('products.push', 'warning', 'Pre-check by SKU failed', [ 'product_id' => $product->id, 'sku' => $sku ]);
                            }
                        }
                    }

                    // Build categories payload from mapped Woo IDs
                    $categoriesPayload = [];
                    try {
                        $cat = $product->category; // may trigger query
                        if ($cat && !empty($cat->woocommerce_id)) {
                            $categoriesPayload[] = ['id' => (int) $cat->woocommerce_id];
                        } elseif ($cat) {
                            $this->log('products.push', 'warning', 'Missing Woo category mapping', [
                                'product_id' => $product->id,
                                'category_id' => $cat->id ?? null,
                                'category_name' => $cat->name ?? null,
                            ]);
                        }
                    } catch (Throwable $e) {
                        $this->log('products.push', 'warning', 'Category resolution failed', [ 'product_id' => $product->id ]);
                    }

                    $isVariant = (int) ($product->is_variant ?? 0) === 1;

                    $payload = [
                        'name'   => (string) ($product->name ?? ''),
                        'type'   => $isVariant ? 'variable' : 'simple',
                        'sku'    => (string) ($product->code ?? ''),
                        'status' => 'publish',
                    ];

                    if (!$isVariant) {
                        $payload['regular_price'] = number_format((float) ($product->price ?? 0), 2, '.', '');
                    } else {
                        // Build a single custom attribute from variant names (e.g., "Variant")
                        try {
                            $variantNames = ProductVariant::where('product_id', $product->id)
                                ->whereNull('deleted_at')
                                ->pluck('name')
                                ->filter(function ($v) { return is_string($v) && trim($v) !== ''; })
                                ->unique()
                                ->values()
                                ->all();
                            if (!empty($variantNames)) {
                                $payload['attributes'] = [[
                                    'name'      => 'Variant',
                                    'visible'   => true,
                                    'variation' => true,
                                    'options'   => array_values($variantNames),
                                ]];
                            }
                        } catch (\Throwable $e) {
                            // If variants cannot be loaded, proceed without attributes, variations will still attempt to sync
                            $this->log('products.push', 'warning', 'Failed preparing variant attributes', [ 'product_id' => $product->id ]);
                        }
                    }

                    if (!empty($categoriesPayload)) {
                        $payload['categories'] = $categoriesPayload;
                    }

                    // Attach main product image if available (prefer WP media id when possible)
                    try {
                        $media = $this->resolveOrUploadWpMedia($product);
                        if ($media && isset($media['id'])) {
                            $payload['images'] = [[ 'id' => (int) $media['id'] ]];
                        } else {
                            $imgSrc = $this->productMainImageSrc($product);
                            if ($imgSrc) {
                                $payload['images'] = [[ 'src' => $imgSrc ]];
                            }
                        }
                    } catch (\Throwable $e) {}

                    $res = null;
                    $wasUpdate = false;
                    if (!empty($product->woocommerce_id)) {
                        $res = $this->client->put('products/' . $product->woocommerce_id, $payload);
                        $wasUpdate = true;
                        if ($res->status() === 404) {
                            // Remote not found, fall back to create
                            $res = $this->client->post('products', $payload);
                            $wasUpdate = false;
                        }
                    } else {
                        $res = $this->client->post('products', $payload);
                        // If create fails due to duplicate SKU, try to fetch by SKU and update instead
                        if (!$res->successful() && in_array($res->status(), [400, 409], true)) {
                            $sku = (string) ($product->code ?? '');
                            if ($sku !== '') {
                                try {
                                    $findRes = $this->client->get('products', ['sku' => $sku]);
                                    if ($findRes->successful()) {
                                        $list = $findRes->json();
                                        if (is_array($list) && count($list) > 0 && isset($list[0]['id'])) {
                                            $product->woocommerce_id = (int) $list[0]['id'];
                                            $product->save();
                                            // Try update now that we have remote id
                                            $res = $this->client->put('products/' . $product->woocommerce_id, $payload);
                                            $wasUpdate = true;
                                        }
                                    }
                                } catch (Throwable $e) {
                                    // fall through to normal error handling
                                }
                            }
                        }
                    }

                    if (!$res->successful()) {
                        $errors++;
                        $this->log('products.push', 'error', 'Woo request failed', [
                            'status' => $res->status(),
                            'body'   => $res->body(),
                            'payload' => $payload,
                            'product_id' => $product->id,
                        ]);
                        continue;
                    }

                    $body = $res->json();
                    $remoteId = (int) ($body['id'] ?? 0);
                    if ($remoteId > 0) {
                        if (empty($product->woocommerce_id) || $product->woocommerce_id !== $remoteId) {
                            $product->woocommerce_id = $remoteId;
                            $product->save();
                        }
                        if ($wasUpdate) $updated++; else $created++;
                        // If variable product, sync its variations now
                        if ($isVariant) {
                            $this->syncVariantsForProduct($product, $remoteId);
                        }
                    } else {
                        $errors++;
                        $this->log('products.push', 'error', 'Missing id in Woo response', [ 'body' => $body, 'product_id' => $product->id ]);
                    }
                } catch (Throwable $e) {
                    $errors++;
                    $this->log('products.push', 'error', $e->getMessage(), [ 'product_id' => $product->id ]);
                } finally {
                    $processed++;
                }
            }

            if ($progress) $progress(['processed' => $processed, 'created' => $created, 'updated' => $updated]);
        });

        $this->log('products.push', 'info', 'Products push completed', ['created' => $created, 'updated' => $updated, 'errors' => $errors]);
        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * Ensure WooCommerce variations exist for the given POS product.
     * Strategy: declare a single custom attribute "Variant" with the list of names,
     * then upsert each variation by SKU (fallback to option match).
     */
    private function syncVariantsForProduct(Product $product, int $wooProductId): void
    {
        try {
            // Refresh attributes on the parent to ensure variations can attach
            $variantNames = ProductVariant::where('product_id', $product->id)
                ->whereNull('deleted_at')
                ->pluck('name')
                ->filter(function ($v) { return is_string($v) && trim($v) !== ''; })
                ->unique()
                ->values()
                ->all();

            if (!empty($variantNames)) {
                $this->client->put('products/' . $wooProductId, [
                    'type'       => 'variable',
                    'attributes' => [[
                        'name'      => 'Variant',
                        'visible'   => true,
                        'variation' => true,
                        'options'   => array_values($variantNames),
                    ]],
                ]);
            }

            // Load existing variations (map by sku and by option)
            $existingMapBySku = [];
            $existingMapByOption = [];
            $page = 1; $perPage = 100;
            while (true) {
                $res = $this->client->get('products/' . $wooProductId . '/variations', ['page' => $page, 'per_page' => $perPage]);
                if (!$res->successful()) break;
                $list = $res->json();
                if (empty($list)) break;
                foreach ($list as $v) {
                    $vid = (int) ($v['id'] ?? 0);
                    if ($vid <= 0) continue;
                    $sku = (string) ($v['sku'] ?? '');
                    if ($sku !== '') $existingMapBySku[$sku] = $vid;
                    $attrs = $v['attributes'] ?? [];
                    if (is_array($attrs) && isset($attrs[0]['option'])) {
                        $opt = (string) $attrs[0]['option'];
                        if ($opt !== '') $existingMapByOption[$opt] = $vid;
                    }
                }
                if (count($list) < $perPage) break;
                $page++;
            }

            // Upsert each variant
            $variants = ProductVariant::where('product_id', $product->id)
                ->whereNull('deleted_at')
                ->get();

            foreach ($variants as $var) {
                $variantName = trim((string) ($var->name ?? ''));
                $sku = trim((string) ($var->code ?? ''));
                if ($sku === '') {
                    // Fallback SKU if missing
                    $sku = $product->code ? ($product->code . '-' . ($variantName !== '' ? $variantName : $var->id)) : ('VAR-' . $var->id);
                }

                $payload = [
                    'sku'           => $sku,
                    'regular_price' => number_format((float) ($var->price ?? 0), 2, '.', ''),
                    'attributes'    => [[
                        'name'   => 'Variant',
                        'option' => $variantName !== '' ? $variantName : ('Variant ' . $var->id),
                    ]],
                ];

                // Attach main product image to variation (prefer WP media id)
                $media = $this->resolveOrUploadWpMedia($product);
                if ($media && isset($media['id'])) {
                    $payload['image'] = [ 'id' => (int) $media['id'] ];
                } else {
                    $imgSrc = $this->productMainImageSrc($product);
                    if ($imgSrc) {
                        $payload['image'] = [ 'src' => $imgSrc ];
                    }
                }

                $targetId = $existingMapBySku[$sku] ?? ($existingMapByOption[$variantName] ?? null);
                try {
                    if ($targetId) {
                        $res = $this->client->put('products/' . $wooProductId . '/variations/' . $targetId, $payload);
                        if (!$res->successful()) {
                            $this->log('variants.push', 'error', 'Failed updating variation', [ 'product_id' => $product->id, 'sku' => $sku, 'status' => $res->status(), 'body' => $res->body() ]);
                        }
                    } else {
                        $res = $this->client->post('products/' . $wooProductId . '/variations', $payload);
                        if (!$res->successful()) {
                            $this->log('variants.push', 'error', 'Failed creating variation', [ 'product_id' => $product->id, 'sku' => $sku, 'status' => $res->status(), 'body' => $res->body() ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->log('variants.push', 'error', $e->getMessage(), [ 'product_id' => $product->id, 'sku' => $sku ]);
                }
            }
        } catch (\Throwable $e) {
            $this->log('variants.push', 'error', 'Variant sync failed for product', [ 'product_id' => $product->id, 'error' => $e->getMessage() ]);
        }
    }

    public function syncStock(callable $progress = null): array
    {
        $updated = 0; $errors = 0; $processed = 0;

        Product::whereNull('deleted_at')
            ->whereNotNull('woocommerce_id')
            ->orderBy('id')
            ->chunk(200, function ($products) use (&$updated, &$errors, &$processed, $progress) {
                foreach ($products as $product) {
                    try {
                        // Skip services (no stock management)
                        if (($product->type ?? '') === 'is_service') {
                            $processed++;
                            if ($progress) $progress(['processed' => $processed, 'updated' => $updated, 'errors' => $errors]);
                            continue;
                        }

                        // Variable products: sync variations individually
                        $isVariant = (int) ($product->is_variant ?? 0) === 1 || ($product->type ?? '') === 'is_variant';
                        if ($isVariant) {
                            $anyInStock = false;

                            // Map existing variations by SKU and option
                            $existingMapBySku = [];
                            $existingMapByOption = [];
                            $page = 1; $per = 100;
                            while (true) {
                                $vres = $this->client->get('products/' . (int) $product->woocommerce_id . '/variations', ['page' => $page, 'per_page' => $per]);
                                if (!$vres->successful()) break;
                                $list = $vres->json();
                                if (empty($list)) break;
                                foreach ($list as $v) {
                                    $vid = (int) ($v['id'] ?? 0);
                                    if ($vid <= 0) continue;
                                    $sku = (string) ($v['sku'] ?? '');
                                    if ($sku !== '') $existingMapBySku[$sku] = $vid;
                                    $attrs = $v['attributes'] ?? [];
                                    if (is_array($attrs) && isset($attrs[0]['option'])) {
                                        $opt = (string) $attrs[0]['option'];
                                        if ($opt !== '') $existingMapByOption[$opt] = $vid;
                                    }
                                }
                                if (count($list) < $per) break;
                                $page++;
                            }

                            // Load local variants
                            $variants = \App\Models\ProductVariant::where('product_id', $product->id)
                                ->whereNull('deleted_at')
                                ->get();

                            foreach ($variants as $var) {
                                $variantName = trim((string) ($var->name ?? ''));
                                $sku = trim((string) ($var->code ?? ''));
                                if ($sku === '') {
                                    $sku = $product->code ? ($product->code . '-' . ($variantName !== '' ? $variantName : $var->id)) : ('VAR-' . $var->id);
                                }
                                $qty = $this->computeVariantStockQuantity((int) $product->id, (int) $var->id);
                                $status = $qty > 0 ? 'instock' : 'outofstock';
                                if ($qty > 0) $anyInStock = true;

                                $payload = [
                                    'manage_stock'   => true,
                                    'stock_quantity' => $qty,
                                    'stock_status'   => $status,
                                ];

                                // Attach main product image to variation on stock sync as well (prefer WP media id)
                                $media = $this->resolveOrUploadWpMedia($product);
                                if ($media && isset($media['id'])) {
                                    $payload['image'] = [ 'id' => (int) $media['id'] ];
                                } else {
                                    $imgSrc = $this->productMainImageSrc($product);
                                    if ($imgSrc) {
                                        $payload['image'] = [ 'src' => $imgSrc ];
                                    }
                                }

                                $targetId = $existingMapBySku[$sku] ?? ($existingMapByOption[$variantName] ?? null);
                                if ($targetId) {
                                    $res = $this->client->put('products/' . (int) $product->woocommerce_id . '/variations/' . $targetId, $payload);
                                    if ($res->successful()) {
                                        $updated++;
                                    } else {
                                        $errors++;
                                    }
                                } else {
                                    // If missing remotely, create it with attribute + sku
                                    $createPayload = $payload + [
                                        'sku' => $sku,
                                        'attributes' => [[ 'name' => 'Variant', 'option' => $variantName !== '' ? $variantName : ('Variant ' . $var->id) ]],
                                    ];
                                    $res = $this->client->post('products/' . (int) $product->woocommerce_id . '/variations', $createPayload);
                                    if ($res->successful()) { $updated++; } else { $errors++; }
                                }
                            }

                            // Update parent stock status only (do not manage stock at parent)
                            $parentPayload = [ 'manage_stock' => false, 'stock_status' => $anyInStock ? 'instock' : 'outofstock' ];
                            try { $this->client->put('products/' . (int) $product->woocommerce_id, $parentPayload); } catch (Throwable $e) {}

                            $processed++;
                            if ($progress) $progress(['processed' => $processed, 'updated' => $updated, 'errors' => $errors]);
                            continue;
                        }

                        // Combo products: quantity = min(component_stock / required_qty)
                        if (($product->type ?? '') === 'is_combo') {
                            $qty = $this->computeComboStockQuantity((int) $product->id);
                            $status = $qty > 0 ? 'instock' : 'outofstock';
                            $payload = [ 'manage_stock' => true, 'stock_quantity' => $qty, 'stock_status' => $status ];
                            $res = $this->client->put('products/' . (int) $product->woocommerce_id, $payload);
                            if ($res->successful()) { $updated++; } else { $errors++; }
                            $processed++;
                            if ($progress) $progress(['processed' => $processed, 'updated' => $updated, 'errors' => $errors]);
                            continue;
                        }

                        // Simple product
                        $qty = $this->computeStockQuantity((int) $product->id);
                        $status = $qty > 0 ? 'instock' : 'outofstock';
                        $payload = [ 'manage_stock' => true, 'stock_quantity' => $qty, 'stock_status' => $status ];
                        $res = $this->client->put('products/' . (int) $product->woocommerce_id, $payload);
                        if ($res->successful()) { $updated++; } else { $errors++; }
                        $processed++;
                        if ($progress) $progress(['processed' => $processed, 'updated' => $updated, 'errors' => $errors]);
                    } catch (Throwable $e) {
                        $errors++;
                        $processed++;
                        if ($progress) $progress(['processed' => $processed, 'updated' => $updated, 'errors' => $errors]);
                    }
                }
            });

        $this->log('stock.sync', 'info', 'Stock sync completed', ['updated' => $updated, 'errors' => $errors]);
        return ['updated' => $updated, 'errors' => $errors];
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
        // components are in combined_products table rows where product_id = combo id
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

    private function productMainImageSrc(Product $product): ?string
    {
        try {
            $imageName = (string) ($product->image ?? '');
            if ($imageName === '' || strtolower($imageName) === 'no-image.png') return null;
            $public = public_path('images/products/' . $imageName);
            if (!is_file($public)) return null;
            return asset('images/products/' . $imageName);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve or upload the product's main image to WordPress media using Application Passwords.
     * Returns ['id' => int, 'src' => string] on success, or null if not available/failed.
     */
    private function resolveOrUploadWpMedia(Product $product): ?array
    {
        try {
            $settings = WooCommerceSetting::first();
            if (!$settings) return null;
            $username = (string) ($settings->wp_username ?? '');
            $appPass  = (string) ($settings->wp_app_password ?? '');
            $baseUrl  = rtrim((string) ($settings->store_url ?? ''), '/');
            if ($username === '' || $appPass === '' || $baseUrl === '') {
                // Missing credentials: skip silently
                return null;
            }

            $imageName = (string) ($product->image ?? '');
            if ($imageName === '' || strtolower($imageName) === 'no-image.png') return null;
            $abs = public_path('images/products/' . $imageName);
            if (!is_file($abs)) return null;

            // 1) Try to find existing media by searching filename (without extension)
            $filenameBase = pathinfo($imageName, PATHINFO_FILENAME);
            $mediaList = Http::timeout(30)
                ->retry(2, 500)
                ->withBasicAuth($username, $appPass)
                ->get($baseUrl . '/wp-json/wp/v2/media', [ 'search' => $filenameBase, 'per_page' => 50 ]);
            if ($mediaList->successful()) {
                $items = $mediaList->json();
                if (is_array($items)) {
                    foreach ($items as $m) {
                        $src = (string) ($m['source_url'] ?? '');
                        if ($src !== '' && (stripos($src, $imageName) !== false || stripos($src, $filenameBase) !== false)) {
                            $id = (int) ($m['id'] ?? 0);
                            if ($id > 0) return [ 'id' => $id, 'src' => $src ];
                        }
                    }
                }
            } else {
                try { WooCommerceLog::create(['action' => 'media.resolve', 'level' => 'error', 'message' => 'WP media search failed', 'context' => ['status' => $mediaList->status(), 'body' => $mediaList->body()]]); } catch (\Throwable $e) {}
            }

            // 2) Upload media
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
                ->attach('file', fopen($abs, 'r'), $imageName)
                ->withHeaders([
                    'Content-Disposition' => 'attachment; filename="' . $imageName . '"',
                    // Let Laravel set multipart Content-Type automatically for attach()
                ])
                ->post($baseUrl . '/wp-json/wp/v2/media');

            if ($upload->successful() || $upload->status() === 201) {
                $body = $upload->json();
                $id = (int) ($body['id'] ?? 0);
                $src = (string) ($body['source_url'] ?? '');
                if ($id > 0) return [ 'id' => $id, 'src' => $src ];
            } else {
                try { WooCommerceLog::create(['action' => 'media.upload', 'level' => 'error', 'message' => 'WP media upload failed', 'context' => ['status' => $upload->status(), 'body' => $upload->body()]]); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {}
        return null;
    }

    public function syncOrders(callable $progress = null): array
    {
        $imported = 0; $errors = 0; $page = 1; $perPage = 25;
        while (true) {
            $res = $this->client->get('orders', ['page' => $page, 'per_page' => $perPage, 'status' => 'any']);
            if (!$res->successful()) break;
            $orders = $res->json();
            if (empty($orders)) break;
            foreach ($orders as $o) {
                try {
                    DB::transaction(function () use ($o) {
                        // Minimal, safe import: do not alter existing core schemas beyond creating Sale and SaleDetails
                        $sale = new Sale();
                        $sale->date = now()->toDateString();
                        $sale->time = now()->format('H:i');
                        $sale->Ref = 'WC-'.($o['id'] ?? '');
                        $sale->client_id = optional(\App\Models\Client::first())->id ?? 1; // fallback to first client
                        $sale->warehouse_id = optional(\App\Models\Warehouse::first())->id ?? 1; // fallback to first warehouse
                        $sale->user_id = auth()->id() ?? 1;
                        $sale->is_pos = 0;
                        $sale->statut = 'completed';
                        $sale->payment_statut = 'paid';
                        $sale->GrandTotal = (float)($o['total'] ?? 0);
                        $sale->discount = 0; $sale->shipping = (float)($o['shipping_total'] ?? 0);
                        $sale->save();

                        foreach (($o['line_items'] ?? []) as $item) {
                            $code = (string)($item['sku'] ?? ("WC-" . ($item['product_id'] ?? '')));
                            $product = Product::where('code', $code)->first();
                            if (!$product) continue; // skip unknown
                            $detail = new SaleDetail();
                            $detail->sale_id = $sale->id;
                            $detail->product_id = $product->id;
                            $detail->quantity = (float)($item['quantity'] ?? 1);
                            $detail->price = isset($item['price']) ? (float)$item['price'] : (float)($product->price ?? 0);
                            $detail->total = round($detail->price * $detail->quantity, 2);
                            $detail->tax_method = '1';
                            $detail->discount = 0;
                            $detail->TaxNet = 0;
                            $detail->save();
                        }
                    }, 3);
                    $imported++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->log('orders.sync', 'error', $e->getMessage(), ['order' => $o]);
                }
            }
            if ($progress) $progress(['page' => $page, 'imported' => $imported]);
            if (count($orders) < $perPage) break;
            $page++;
        }
        $this->log('orders.sync', 'info', 'Orders sync completed', ['imported' => $imported, 'errors' => $errors]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    private function log(string $action, string $level, string $message, array $context = []): void
    {
        WooCommerceLog::create([
            'action' => $action,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    // ----------------- Categories -----------------
    public function pullCategories(callable $progress = null): array
    {
        $synced = 0; $errors = 0; $page = 1; $perPage = 100;
        while (true) {
            $res = $this->client->get('products/categories', ['page' => $page, 'per_page' => $perPage, 'hide_empty' => false]);
            if (!$res->successful()) {
                $this->log('categories.pull', 'error', 'Failed fetching categories page', [
                    'page' => $page,
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);
                break;
            }
            $items = $res->json();
            if (empty($items)) break;
            foreach ($items as $c) {
                try {
                    DB::transaction(function () use ($c) {
                        $wooId = (int) ($c['id'] ?? 0);
                        $name = (string) ($c['name'] ?? '');
                        if ($wooId <= 0 || $name === '') return;
                        $cat = PosCategory::firstOrNew(['woocommerce_id' => $wooId]);
                        if (!$cat->exists) {
                            // try match by name if exists
                            $cat = PosCategory::where('name', $name)->first() ?? $cat;
                        }
                        $cat->name = $name;
                        $cat->woocommerce_id = $wooId;
                        $cat->code = $cat->code ?? 'CAT-' . $wooId;
                        $cat->save();
                    }, 3);
                    $synced++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->log('categories.pull', 'error', $e->getMessage(), ['trace' => $e->getTraceAsString(), 'category' => $c]);
                }
            }
            if ($progress) $progress(['page' => $page, 'synced' => $synced]);
            if (count($items) < $perPage) break;
            $page++;
        }
        $this->log('categories.pull', 'info', 'Categories pull completed', ['synced' => $synced, 'errors' => $errors]);
        return ['synced' => $synced, 'errors' => $errors];
    }

    public function pushCategories(bool $onlyUnsynced = false, callable $progress = null): array
    {
        $created = 0; $updated = 0; $errors = 0; $processed = 0;
        $query = PosCategory::whereNull('deleted_at');
        if ($onlyUnsynced) $query->whereNull('woocommerce_id');

        $query->orderBy('id')->chunk(100, function ($categories) use (&$created, &$updated, &$errors, &$processed, $progress) {
            foreach ($categories as $cat) {
                try {
                    // Pre-link by name to avoid duplicates when switching stores
                    if (empty($cat->woocommerce_id)) {
                        $name = (string) ($cat->name ?? '');
                        if ($name !== '') {
                            try {
                                $findRes = $this->client->get('products/categories', [ 'search' => $name, 'per_page' => 100, 'hide_empty' => false ]);
                                if ($findRes->successful()) {
                                    $list = $findRes->json();
                                    if (is_array($list) && count($list) > 0) {
                                        foreach ($list as $remote) {
                                            if (isset($remote['id'], $remote['name']) && strcasecmp((string)$remote['name'], $name) === 0) {
                                                $cat->woocommerce_id = (int) $remote['id'];
                                                $cat->save();
                                                $this->log('categories.push', 'info', 'Linked existing Woo category by name', [
                                                    'category_id' => $cat->id,
                                                    'name' => $name,
                                                    'woocommerce_id' => $cat->woocommerce_id,
                                                ]);
                                                break;
                                            }
                                        }
                                    }
                                }
                            } catch (\Throwable $e) {
                                // ignore pre-link errors
                            }
                        }
                    }

                    $payload = [ 'name' => (string) ($cat->name ?? '') ];
                    $res = null; $wasUpdate = false;
                    if (!empty($cat->woocommerce_id)) {
                        $res = $this->client->put('products/categories/' . $cat->woocommerce_id, $payload);
                        $wasUpdate = true;
                        if ($res->status() === 404) {
                            $res = $this->client->post('products/categories', $payload);
                            $wasUpdate = false;
                        }
                    } else {
                        $res = $this->client->post('products/categories', $payload);
                    }

                    if (!$res->successful()) {
                        $errors++;
                        $this->log('categories.push', 'error', 'Woo request failed', [
                            'status' => $res->status(),
                            'body'   => $res->body(),
                            'category_id' => $cat->id,
                        ]);
                        continue;
                    }

                    $body = $res->json();
                    $remoteId = (int) ($body['id'] ?? 0);
                    if ($remoteId > 0) {
                        if (empty($cat->woocommerce_id) || $cat->woocommerce_id !== $remoteId) {
                            $cat->woocommerce_id = $remoteId;
                            $cat->save();
                        }
                        if ($wasUpdate) $updated++; else $created++;
                    } else {
                        $errors++;
                        $this->log('categories.push', 'error', 'Missing id in Woo response', [ 'body' => $body, 'category_id' => $cat->id ]);
                    }
                } catch (Throwable $e) {
                    $errors++;
                    $this->log('categories.push', 'error', $e->getMessage(), [ 'category_id' => $cat->id ]);
                } finally {
                    $processed++;
                }
            }
            if ($progress) $progress(['processed' => $processed, 'created' => $created, 'updated' => $updated]);
        });

        $this->log('categories.push', 'info', 'Categories push completed', ['created' => $created, 'updated' => $updated, 'errors' => $errors]);
        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }
}


