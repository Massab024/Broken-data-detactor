<?php

namespace App\Http\Controllers;

use App\Models\Products\Product;
use App\Services\ProductValidationService;
use App\Jobs\ProductValidationJob;
use Illuminate\Http\Request;

class ProductValidationController extends Controller
{
    public function validateAll(Request $request, ProductValidationService $validationService)
    {
        ProductValidationJob::dispatch($request->user()->id);

        return $this->sendResponse([], 'Product validation has been queued.');
    }

    public function validateSingleProduct(Request $request, int $product, ProductValidationService $validationService)
    {
        $resolvedProduct = Product::query()
            ->where('id', $product)
            ->where('user_id', $request->user()?->id)
            ->firstOrFail();

        $validationService->validateProduct($resolvedProduct, $request->user()->id);

        return back()->with('success', 'Product validation completed');
    }
}
