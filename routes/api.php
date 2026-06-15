<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArtworkVersionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/exercise-1-artwork-version', [ArtworkVersionController::class, 'artworkVersion']);
Route::post('/exercise-2-tier-pricing', [ArtworkVersionController::class, 'tierPricing']);
Route::post('/exercise-3-cart-validator', [ArtworkVersionController::class, 'cartValidator']);
Route::post('/exercise-4-vendor-allocation', [ArtworkVersionController::class, 'vendorAllocation']);
Route::post('/exercise-5-discount', [ArtworkVersionController::class, 'discount']);
Route::post('/exercise-6-approval-flow', [ArtworkVersionController::class, 'approvalFlow']);
Route::post('/exercise-7-inventory', [ArtworkVersionController::class, 'inventory']);
Route::post('/exercise-8-shipment', [ArtworkVersionController::class, 'shipment']);
Route::post('/exercise-9-webhook', [ArtworkVersionController::class, 'webhook']);
Route::post('/exercise-10-quote-expiry', [ArtworkVersionController::class, 'quoteExpiry']);
Route::post('/exercise-11-product-visibility', [ArtworkVersionController::class, 'productVisibility']);
Route::post('/exercise-12-bundle-pricing', [ArtworkVersionController::class, 'bundelPricing']);
Route::post('/exercise-13-cart-merge', [ArtworkVersionController::class, 'cartMerge']);
Route::post('/exercise-14-upsell', [ArtworkVersionController::class, 'upsell']);
Route::post('/exercise-15-shipping-rule', [ArtworkVersionController::class, 'shippingRule']);
Route::post('/exercise-16-fraud-check', [ArtworkVersionController::class, 'fraudCheck']);
Route::post('/exercise-17-product-price-engine', [ArtworkVersionController::class, 'productPriceEngine']);
Route::post('/exercise-18-data-sync', [ArtworkVersionController::class, 'dataSync']);
Route::post('/exercise-19-variant-control ', [ArtworkVersionController::class, 'variantControl']);
Route::post('/exercise-20-order-state', [ArtworkVersionController::class, 'orderState']);
