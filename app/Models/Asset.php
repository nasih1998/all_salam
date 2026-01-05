<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'tag',
        'name',
        'asset_category_id',
        'serial_number',
        'description',
        'purchase_date',
        'purchase_cost',
        'status',
        'warehouse_id',
        'assigned_to_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'purchase_cost' => 'double',
    ];

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}


