<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Warehouse;
use App\Models\AssetCategory;
use App\Models\UserWarehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AssetController extends BaseController
{
    //-------------- Get All Assets ---------------\\
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Asset::class);

        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?: 'id';
        $dir = strtolower($request->SortType ?: 'desc');
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'desc';
        }

        $sortableMap = [
            'id' => 'assets.id',
            'name' => 'assets.name',
            'tag' => 'assets.tag',
            'serial_number' => 'assets.serial_number',
            'status' => 'assets.status',
            'purchase_date' => 'assets.purchase_date',
            'purchase_cost' => 'assets.purchase_cost',
            'warehouse_name' => 'warehouse_name',
            'asset_category_name' => 'asset_category_name',
        ];
        $order = $sortableMap[$order] ?? 'assets.id';

        $query = Asset::leftJoin('warehouses', 'warehouses.id', '=', 'assets.warehouse_id')
            ->leftJoin('asset_categories', 'asset_categories.id', '=', 'assets.asset_category_id')
            ->whereNull('assets.deleted_at')
            ->select('assets.*', 'warehouses.name as warehouse_name', 'asset_categories.name as asset_category_name')
            ->where(function ($q) use ($request) {
                return $q->when($request->filled('search'), function ($q) use ($request) {
                    $s = $request->search;
                    return $q->where('name', 'LIKE', "%{$s}%")
                        ->orWhere('tag', 'LIKE', "%{$s}%")
                        ->orWhere('asset_categories.name', 'LIKE', "%{$s}%")
                        ->orWhere('serial_number', 'LIKE', "%{$s}%")
                        ->orWhere('status', 'LIKE', "%{$s}%")
                        ->orWhere('warehouses.name', 'LIKE', "%{$s}%");
                });
            });

        $totalRows = $query->count();
        if ($perPage == "-1") {
            $perPage = $totalRows;
        }

        $assets = $query->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($assets as $asset) {
            $item['id'] = $asset->id;
            $item['tag'] = $asset->tag;
            $item['name'] = $asset->name;
            $item['asset_category_id'] = $asset->asset_category_id;
            $item['asset_category_name'] = $asset->asset_category_name ?? null;
            $item['serial_number'] = $asset->serial_number;
            $item['asset_category_id'] = $asset->asset_category_id;
            $item['status'] = $asset->status;
            $item['warehouse_id'] = $asset->warehouse_id;
            $item['warehouse_name'] = $asset->warehouse_name ?? null;
            $item['purchase_date'] = $asset->purchase_date;
            $item['purchase_cost'] = $asset->purchase_cost;
            $data[] = $item;
        }

        return response()->json([
            'assets' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    //------------ function show -----------\\
    public function show(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'view', Asset::class);
        $asset = Asset::where('deleted_at', '=', null)->findOrFail($id);
        return response()->json([
            'id' => $asset->id,
            'tag' => $asset->tag,
            'name' => $asset->name,
            'asset_category_id' => $asset->asset_category_id,
            'serial_number' => $asset->serial_number,
            'description' => $asset->description,
            'purchase_date' => $asset->purchase_date,
            'purchase_cost' => $asset->purchase_cost,
            'status' => $asset->status,
            'warehouse_id' => $asset->warehouse_id,
            'assigned_to_id' => $asset->assigned_to_id,
        ]);
    }

    //-------------- Store New Asset ---------------\\
    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Asset::class);

        request()->validate([
            'tag' => 'required|unique:assets,tag',
            'name' => 'required',
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        Asset::create([
            'tag' => $request['tag'],
            'name' => $request['name'],
            'asset_category_id' => $request['asset_category_id'],
            'serial_number' => $request['serial_number'],
            'description' => $request['description'],
            'purchase_date' => $request['purchase_date'],
            'purchase_cost' => $request['purchase_cost'],
            'status' => $request['status'] ?: 'in_use',
            'warehouse_id' => $request['warehouse_id'],
            'assigned_to_id' => $request['assigned_to_id'],
        ]);

        return response()->json(['success' => true], 200);
    }

    //-------------- Update Asset ---------------\\
    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Asset::class);
        $asset = Asset::findOrFail($id);

        request()->validate([
            'tag' => 'required|unique:assets,tag,' . $id,
            'name' => 'required',
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $asset->update([
            'tag' => $request['tag'],
            'name' => $request['name'],
            'asset_category_id' => $request['asset_category_id'],
            'serial_number' => $request['serial_number'],
            'description' => $request['description'],
            'purchase_date' => $request['purchase_date'],
            'purchase_cost' => $request['purchase_cost'],
            'status' => $request['status'] ?: 'in_use',
            'warehouse_id' => $request['warehouse_id'],
            'assigned_to_id' => $request['assigned_to_id'],
        ]);

        return response()->json(['success' => true], 200);
    }

    //-------------- Delete Asset ---------------\\
    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Asset::class);
        $asset = Asset::findOrFail($id);
        $asset->update(['deleted_at' => Carbon::now()]);
        return response()->json(['success' => true], 200);
    }

    //-------------- Delete by selection  ---------------\\
    public function delete_by_selection(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Asset::class);
        $selectedIds = $request->selectedIds ?: [];
        foreach ($selectedIds as $assetId) {
            $asset = Asset::findOrFail($assetId);
            $asset->update(['deleted_at' => Carbon::now()]);
        }
        return response()->json(['success' => true], 200);
    }

    //-------------- Prefill elements for asset forms ---------------\\
    public function create()
    {
        $this->authorizeForUser(request()->user('api'), 'view', Asset::class);

        $user_auth = auth()->user();
        if ($user_auth && $user_auth->is_all_warehouses) {
            $warehouses = Warehouse::where('deleted_at', '=', null)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $warehouses_id = UserWarehouse::where('user_id', $user_auth ? $user_auth->id : null)
                ->pluck('warehouse_id')
                ->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)
                ->whereIn('id', $warehouses_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $asset_categories = AssetCategory::whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'warehouses' => $warehouses,
            'asset_categories' => $asset_categories,
        ]);
    }

    //-------------- Edit payload (asset + selects) ---------------\\
    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'view', Asset::class);

        $asset = Asset::where('deleted_at', '=', null)->findOrFail($id);

        $user_auth = auth()->user();
        if ($user_auth && $user_auth->is_all_warehouses) {
            $warehouses = Warehouse::where('deleted_at', '=', null)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $warehouses_id = UserWarehouse::where('user_id', $user_auth ? $user_auth->id : null)
                ->pluck('warehouse_id')
                ->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)
                ->whereIn('id', $warehouses_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $asset_categories = AssetCategory::whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'asset' => [
                'id' => $asset->id,
                'tag' => $asset->tag,
                'name' => $asset->name,
                'asset_category_id' => $asset->asset_category_id,
                'serial_number' => $asset->serial_number,
                'description' => $asset->description,
                'purchase_date' => $asset->purchase_date,
                'purchase_cost' => $asset->purchase_cost,
                'status' => $asset->status,
                'warehouse_id' => $asset->warehouse_id,
                'assigned_to_id' => $asset->assigned_to_id,
            ],
            'warehouses' => $warehouses,
            'asset_categories' => $asset_categories,
        ]);
    }

    //-------------- Warehouses list for assets (minimal/select) ---------------\\
    public function warehouses(Request $request)
    {
        // $this->authorizeForUser($request->user('api'), 'view', Asset::class);

        $user_auth = auth()->user();
        if ($user_auth && $user_auth->is_all_warehouses) {
            $warehouses = Warehouse::where('deleted_at', '=', null)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $warehouses_id = UserWarehouse::where('user_id', $user_auth ? $user_auth->id : null)
                ->pluck('warehouse_id')
                ->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)
                ->whereIn('id', $warehouses_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return response()->json(['data' => $warehouses]);
    }
}


