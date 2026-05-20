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

    public function validateSingleProduct(Request $request, Product $product, ProductValidationService $validationService)
    {
        abort_unless($request->user()?->id === $product->user_id, 403);

        $result = $validationService->validateProduct($product, $request->user()->id);

        return back()->with('success', 'Product validation completed');
    }
}
