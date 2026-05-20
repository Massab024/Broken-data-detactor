<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $fillable = [
        'user_id',
        'shopify_product_id',
        'title',
        'handle',
        'body_html',
        'tags',
        'vendor',
        'product_type',
        'status',
        'image_url',
        'has_variants',
        'min_price',
        'max_price',
        'health_status',
        'last_synced_at',
        'last_checked_at',
        'raw_data',
        'variant_count',
    ];

    protected $casts = [
        'has_variants' => 'boolean',
        'raw_data' => 'array',
        'last_synced_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function productVarients()
    {
        return $this->hasMany(ProductVarient::class);
    }
    public function productMedias()
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function productIssues()
    {
        return $this->hasMany(ProductIssue::class);
    }
}
