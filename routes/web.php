<?php

use Osiset\ShopifyApp\Util;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use Inertia\Inertia;

Route::group(['middleware' => ['verify.embedded', 'verify.shopify']], function () {

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/product-issues', fn () => Inertia::render('Embedded/ProductIssues'))->name('product.issues');
    Route::get('/validation-settings', fn () => Inertia::render('Embedded/ValidationSettings'))->name('validation.settings');
    Route::get('/logs', fn () => Inertia::render('Embedded/Logs'))->name('logs');

});

require __DIR__ . '/auth.php';
