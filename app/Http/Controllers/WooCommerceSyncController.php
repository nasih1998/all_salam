<?php

namespace App\Http\Controllers;

use App\Models\WooCommerceLog;
use App\Models\WooCommerceSetting;
use App\Services\WooCommerce\SyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use App\Jobs\WooCommerceStockSyncJob;

class WooCommerceSyncController extends BaseController
{
    public function getSettings(Request $request)
    {
        $settings = WooCommerceSetting::first();
        return response()->json(['settings' => $settings]);
    }

    public function saveSettings(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);
        
        $data = $request->validate([
            'store_url' => 'required|string',
            'consumer_key' => 'required|string',
            'consumer_secret' => 'required|string',
            'wp_username' => 'nullable|string',
            'wp_app_password' => 'nullable|string',
            'sync_interval' => 'nullable|string',
        ]);

        // Detect store change before saving
        $existing = WooCommerceSetting::first();
        $storeChanged = false;
        if ($existing) {
            $prevUrl = rtrim((string) $existing->store_url, '/');
            $newUrl = rtrim((string) $data['store_url'], '/');
            $prevKey = (string) $existing->consumer_key;
            $newKey = (string) $data['consumer_key'];
            $prevSecret = (string) $existing->consumer_secret;
            $newSecret = (string) $data['consumer_secret'];
            // Consider store changed if URL, key or secret changed
            $storeChanged = ($prevUrl !== $newUrl) || ($prevKey !== $newKey) || ($prevSecret !== $newSecret);
        }

        $settings = null;
        DB::transaction(function () use ($data, &$settings) {
            $settings = WooCommerceSetting::first();
            if (!$settings) $settings = new WooCommerceSetting();
            foreach ($data as $k => $v) $settings->$k = $v;
            $settings->save();
        }, 3);

        // If the Woo store has changed, clear previous product/category mappings so they can be re-synced to the new store
        if ($storeChanged && $settings) {
            DB::transaction(function () use (&$settings) {
                DB::table('products')->whereNotNull('woocommerce_id')->update([
                    'woocommerce_id' => null,
                    'updated_at' => now(),
                ]);
                DB::table('categories')->whereNotNull('woocommerce_id')->update([
                    'woocommerce_id' => null,
                    'updated_at' => now(),
                ]);
                $settings->last_sync_at = null;
                $settings->save();
            }, 3);
        }

        return response()->json(['success' => true, 'settings' => $settings]);
    }

    public function connectStore(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        $settings = WooCommerceSetting::first();
        if (!$settings) {
            return response()->json(['ok' => false, 'error' => 'WooCommerce not configured'], 422);
        }
        if (empty($settings->store_url) || empty($settings->consumer_key) || empty($settings->consumer_secret)) {
            return response()->json(['ok' => false, 'error' => 'Missing WooCommerce credentials'], 422);
        }

        $sync = SyncService::fromSettings($settings);
        $result = $sync->testConnection();
        if (empty($result['ok'])) {
            // persist failure details for troubleshooting
            \App\Models\WooCommerceLog::create([
                'action' => 'connect.test',
                'level' => 'error',
                'message' => 'Connection test failed',
                'context' => [
                    'status' => $result['status'] ?? null,
                    'data' => $result['data'] ?? null,
                    'error' => $result['error'] ?? null,
                ],
            ]);
        }
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function syncProducts(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        $settings = WooCommerceSetting::first();
        if (!$settings) return response()->json(['ok' => false, 'error' => 'WooCommerce not configured'], 422);
        $sync = SyncService::fromSettings($settings);
        // Enforce push-only (Stocky → WooCommerce)
        $onlyUnsynced = (bool) $request->boolean('only_unsynced', false);
        $result = $sync->pushProducts($onlyUnsynced);
        $settings->last_sync_at = now();
        $settings->save();
        return response()->json(['ok' => true, 'result' => $result]);
    }

    public function syncStock(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        $settings = WooCommerceSetting::first();
        if (!$settings) return response()->json(['ok' => false, 'error' => 'WooCommerce not configured'], 422);

        // Start queued job and return a progress token
        $token = 'woo_stock_sync_' . uniqid();
        Cache::put($token, [
            'total_products' => 0,
            'synced_products' => 0,
            'failed_products' => 0,
            'percentage' => 0,
            'started_at' => now()->toDateTimeString(),
            'finished' => false,
        ], 3600);
        WooCommerceStockSyncJob::dispatch($token);
        return response()->json(['ok' => true, 'token' => $token]);
    }

    /**
     * GET /api/woocommerce/sync-stock/progress?token=...
     */
    public function syncStockProgress(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);
        $token = (string) $request->query('token');
        if ($token === '') return response()->json(['ok' => false, 'error' => 'Missing token'], 422);
        $state = Cache::get($token, null);
        return response()->json(['ok' => $state !== null, 'state' => $state]);
    }

    /**
     * GET /api/woocommerce/stock-metrics
     */
    public function stockMetrics(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);
        $in = (int) \DB::table('product_warehouse')
            ->whereNull('deleted_at')
            ->selectRaw('product_id, SUM(qte) as total')
            ->groupBy('product_id')
            ->having('total', '>', 0)
            ->count();
        $out = (int) \DB::table('product_warehouse')
            ->whereNull('deleted_at')
            ->selectRaw('product_id, SUM(qte) as total')
            ->groupBy('product_id')
            ->having('total', '<=', 0)
            ->count();
        $last = optional(\App\Models\WooCommerceSetting::first())->last_sync_at;
        return response()->json([
            'in_stock' => $in,
            'out_stock' => $out,
            'last_sync' => $last,
        ]);
    }

    

    public function syncCategories(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        $settings = WooCommerceSetting::first();
        if (!$settings) return response()->json(['ok' => false, 'error' => 'WooCommerce not configured'], 422);
        $sync = SyncService::fromSettings($settings);
        $mode = (string) $request->query('mode', 'push');
        if ($mode === 'pull') {
            $result = $sync->pullCategories();
        } else {
            $onlyUnsynced = (bool) $request->boolean('only_unsynced', false);
            $result = $sync->pushCategories($onlyUnsynced);
        }
        $settings->last_sync_at = now();
        $settings->save();
        return response()->json(['ok' => true, 'result' => $result]);
    }

    public function syncOrders(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        // Push-only mode: pulling orders from WooCommerce is disabled
        return response()->json(['ok' => false, 'error' => 'Orders pull is disabled (push-only mode: Stocky → Woo)'], 405);
    }

    public function logs(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);
        
        $logs = WooCommerceLog::orderBy('id', 'desc')->limit(200)->get();
        return response()->json(['data' => $logs]);
    }

    /**
     * DELETE /woocommerce/logs
     * Clear WooCommerce sync logs.
     */
    public function clearLogs(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);
        WooCommerceLog::query()->delete();
        return response()->json(['success' => true]);
    }

    /**
     * POST /woocommerce/reset-sync
     * Reset sync state for products, categories, logs, and last sync timestamp.
     */
    public function resetSync(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        DB::transaction(function () {
            // Clear mappings
            DB::table('products')->whereNotNull('woocommerce_id')->update([
                'woocommerce_id' => null,
                'updated_at' => now(),
            ]);
            DB::table('categories')->whereNotNull('woocommerce_id')->update([
                'woocommerce_id' => null,
                'updated_at' => now(),
            ]);

            // Clear logs
            WooCommerceLog::query()->delete();

            // Reset last sync
            $settings = WooCommerceSetting::first();
            if ($settings) {
                $settings->last_sync_at = null;
                $settings->save();
            }
        }, 3);

        return response()->json(['success' => true]);
    }

    public function unsyncedCount(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        $count = Product::whereNull('deleted_at')->whereNull('woocommerce_id')->count();
        return response()->json(['count' => $count]);
    }

    public function unsyncedCategoriesCount(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);
        $count = Category::whereNull('deleted_at')->whereNull('woocommerce_id')->count();
        return response()->json(['count' => $count]);
    }

    /**
     * POST /woocommerce/categories/map
     * Body: { mappings: [ { id: <local_category_id>, woocommerce_id: <int|null> }, ... ] }
     */
    public function mapCategories(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', WooCommerceSetting::class);

        $data = $request->validate([
            'mappings' => 'required|array',
            'mappings.*.id' => 'required|integer|exists:categories,id',
            'mappings.*.woocommerce_id' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['mappings'] as $map) {
                DB::table('categories')->where('id', $map['id'])->update([
                    'woocommerce_id' => $map['woocommerce_id'] ?? null,
                    'updated_at' => now(),
                ]);
            }
        }, 3);

        return response()->json(['success' => true]);
    }
}


