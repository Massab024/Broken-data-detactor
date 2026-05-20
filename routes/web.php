<?php

use Osiset\ShopifyApp\Util;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductIssueController;
use App\Http\Controllers\ProductValidationController;
use Inertia\Inertia;

Route::group(['middleware' => ['verify.embedded', 'verify.shopify']], function () {

    Route::get('/', [DashboardController::class, 'index'])->name('home');

    Route::post('/products/sync', [DashboardController::class, 'syncProducts'])->name('products.sync');
    Route::post('/products/validate', [ProductValidationController::class, 'validateAll'])->name('products.validate');
    Route::post('/products/{product}/validate', [ProductValidationController::class, 'validateSingleProduct'])->name('products.validate.single');
    Route::get('/product-issues', [ProductIssueController::class, 'index'])->name('product.issues');
    Route::get('/validation-settings', fn () => Inertia::render('Embedded/ValidationSettings'))->name('validation.settings');
    Route::get('/logs', fn () => Inertia::render('Embedded/Logs'))->name('logs');

});

require __DIR__ . '/auth.php';
