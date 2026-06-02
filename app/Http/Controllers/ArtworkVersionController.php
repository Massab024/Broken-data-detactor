<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ArtworkVersionController extends Controller
{
    public function artworkVersion(Request $request)
    {

        // return $request->input;
        //    $data =  array $request->input, $requestnull $request = null
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }

        $validator = Validator::make($request->all(), [
            'input.*.id' => 'required|integer',
            'input.*.approved' => 'required|boolean',
            'input.*.rejected' => 'required|boolean',
            'input.*.time' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 400);
        }
        $formatedData = null;
        $largestTime = 0;

        foreach ($request->input as $data) {
            // $formatedData = $data;

            if ($data["approved"] == true && $data["rejected"] == false &&  $data["time"] >= $largestTime) {
                $formatedData = $data;
                $largestTime = $data["time"];
            }
        }
        if (!$formatedData) {
            return response()->json([
                'success' => true,
                'data'  => "No approved version found",
                // 'data' => $tierGet,
                'error' => null,
            ], 200);
        }
        return response()->json([
            'success' => true,
            // 'message' => $formatedData,
            'data'  => ["id" => $formatedData["id"]],
            // 'message' => $largestTime,
            'error' => null,
        ], 200);
    }

    public function tierPricing(Request $request)
    {
        // return $request;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }
        $validator = Validator::make($request->all(), [
            'input.quantity'  =>  'required|integer',
            'input.tiers.*.min' => 'required|integer',
            'input.tiers.*.price' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 400);
        }
        $sortedTires = collect($request->input["tiers"]);
        $sortedTires =  $sortedTires->sortBy('min');
        $tierGet = null;

        foreach ($sortedTires as $tier) {


            if ($tier["min"] <= $request->input["quantity"]) {
                $tierGet = $tier;
            }
        }

        if (!$tierGet) {
            return response()->json([
                'success' => true,
                'data'  => "No tier found for this",
                // 'data' => $tierGet,
                'error' => null,
            ], 200);
        }
        return response()->json([
            'success' => true,
            'data'  => ["price" => $tierGet["price"]],
            // 'data' => $tierGet,
            'error' => null,
        ], 200);
    }
    public function cartValidator(Request $request)
    {
        // return $request;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }
        $validator = Validator::make($request->all(), [
            'input.*.id' => 'required|integer',
            'input.*.required' => 'required|boolean',
            'input.*.done' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 400);
        }
        $invalidItems = array();
        foreach ($request->input as $data) {
            // $formatedData = $data;

            if ($data["required"] == true && $data["done"] == false) {
                // $formatedData = $data;
                // $invalidItems->push($data["id"]);
                $invalidItems[] = $data["id"];
            }
        }
        // return $invalidItems;
        if (!$invalidItems) {
            return response()->json([
                'success' => true,
                'data'  => "No invalid items found",
                // 'data' => $tierGet,
                'error' => null,
            ], 200);
        }
        return response()->json([
            'success' => true,
            // 'message' => $formatedData,
            'data'  => [
                "valid" => false,
                "invalid_items" =>  $invalidItems
            ],
            // 'message' => $largestTime,
            'error' => null,
        ], 200);
    }
    public function vendorAllocation(Request $request)
    {
        // return $request->input['order_qty'];
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }
        $validator = Validator::make($request->all(), [
            'input.order_qty'  =>  'required|integer',
            'input.vendors.*.id' => 'required|integer',
            'input.vendors.*.stock' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 400);
        }
        $sortedVendors = collect($request->input['vendors']);
        $sortedVendors =  $sortedVendors->sortBy('stock');
        $allocation = array();
        $remainingQuantity = $request->input['order_qty'];

        foreach ($sortedVendors as $vendor) {
            if ($remainingQuantity <= 0) {
                break;
            }

            if ($vendor["stock"] <= $remainingQuantity) {
                $allocation[] = [
                    "vendor_id" => $vendor["id"],
                    "allocated" => $vendor["stock"],

                ];
                $remainingQuantity -= $vendor["stock"];
            } else {
                $allocation[] = [
                    "vendor_id" => $vendor["id"],
                    "allocated" => $remainingQuantity,
                ];
                $remainingQuantity = 0;
            }
        }

        if ($remainingQuantity > 0) {
            return response()->json([
                'success' => false,
                'data'  => ["allocation" => []],

                'error' => "Not enough stock to fulfill the order",
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data'  => ["allocation" => $allocation],

            'error' => null,
        ], 200);
    }
    public function discount(Request $request)
    {
        // return $request;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }
        $validator = Validator::make($request->all(), [
            'input.price'  =>  'required|integer',
            'input.discounts.*.type' => 'required|string',
            'input.discounts.*.value' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 400);
        }
        $discounts = collect($request->input['discounts']);

        $discountsValues = array();
        $price = $request->input['price'];
        $maxDiscount = 0;
        $calulatedDiscount = null;
        foreach ($discounts as $discount) {
            $discountTypeCheck = strtolower($discount["type"]);

            if ($discountTypeCheck == "percentage") {
                $calulatedDiscount = $price * ($discount["value"] / 100);
                $discountsValues[] = [
                    "type" => $discount["type"],
                    "discount_amount" => $calulatedDiscount,


                ];
                if ($maxDiscount <= $calulatedDiscount) {
                    $maxDiscount = $calulatedDiscount;
                }
                // $remainingQuantity -= $vendor["stock"];

            } else if ($discountTypeCheck == "flat") {
                $discountsValues[] = [
                    "type" => $discount["type"],
                    "discount_amount" => $discount["value"],

                ];
                if ($maxDiscount <= $discount["value"]) {
                    $maxDiscount = $discount["value"];
                }
            } else {
                return response()->json([
                    'success' => false,

                    'error' => "only fixed & percentage used for discount type check your data",
                ], 200);
            }
        }

        return response()->json([
            'success' => true,
            'data'  => ["final_price" => $price - $maxDiscount],


            'error' => null,
        ], 200);
    }

    public function approvalFlow(Request $request)
    {
        // return $request;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }
        $validator = Validator::make($request->all(), [

            'input.steps.*.id' => 'required|string',
            'input.steps.*.depends_on' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 400);
        }

        $steps = $request->input['steps'];

        $resolved = [];
        $remaining = $steps;
        $n = count($steps);

        for ($pass = 0; $pass < $n; $pass++) {

            $progress = false;

            for ($i = 0; $i < count($remaining); $i++) {

                $step = $remaining[$i];
                $id = $step['id'];
                $dependsOn = $step['depends_on'];

                if ($dependsOn == null || in_array($dependsOn, $resolved)) {

                    $resolved[] = $id;

                    array_splice($remaining, $i, 1);
                    $progress = true;

                    $i--;
                }
            }


            if (count($remaining) == 0) {
                return response()->json([
                    'success' => true,
                    'data' => ['valid' => true],
                    'error' => null,
                ], 200);
            }

            if (!$progress) {
                return response()->json([
                    'success' => false,
                    'data' => ['valid' => false],
                    'error' => 'Invalid workflow ',
                ], 400);
            }
        }

        return response()->json([
            'success' => false,
            'data' => ['valid' => false],
            'error' => 'Unexpected error',
        ], 400);
    }
    public function inventory(Request $request)
    {
        // return $request;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }

        $validator = Validator::make($request->all(), [
            'input.stock' => 'required|integer|min:0',
            'input.requests' => 'required|array',
            'input.requests.*' => 'integer|min:1',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 400);
        }
        $stock = $request->input['stock'];
        $stockLeft = $request->input['stock'];
        $stockRequests = $request->input['requests'];
        $requestRequest = [];

        foreach ($stockRequests as $stockRequest) {
            // return $stockRequest;
            $stockLeft   = $stockLeft -  $stockRequest;
            if ($stockLeft >= 0) {
                $requestRequest[] = true;
            } else {
                $requestRequest[] = false;
            }
        }
        // return $requestRequest;

        return response()->json([
            'success' => true,
            'data' => $requestRequest,
            'error' => null,
        ], 200);
    }
    public function shipment(Request $request)
    {
        // return $request;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }

        $validator = Validator::make($request->all(), [
            'input.ordered' => 'required|integer|min:1',
            'input.shipped' => 'required|array',
            'input.shipped.*' => 'integer|min:1',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
            ], 200);
        }

        $stockRequests = $request->input['shipped'];
        $totalShiped  = 0;
        $remainingQuantity = 0;

        foreach ($stockRequests as $stockRequest) {
            $totalShiped = $totalShiped + $stockRequest;
        }
        // return $requestRequest;
        $remainingQuantity = $request->input['ordered'] - $totalShiped;
        if ($remainingQuantity > $request->input['ordered'] || $remainingQuantity < 0) {
            return response()->json([
                'success' => false,
                'data' => [],
                'error' => "Shipped quantity must be less or equal to the ordered quantity ",
            ], 200);
        }
        return response()->json([
            'success' => true,
            'data' => ['remaining' => $remainingQuantity],
            'error' => null,
        ], 200);
    }
    public function webhook(Request $request)
    {
        // return $request->input;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [
            'input' => 'required|array',
            'input.*.id' => 'required|string',
            'input.*.time' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }


        $sortedWebhooks = collect($request->input);
        $sortedWebhookArray =  $sortedWebhooks->sortBy('time');
        $dataSortedFiltered = $sortedWebhookArray->unique('id');
        $data = [];
        foreach ($dataSortedFiltered as $dataSortedFilter) {

            $data[] = $dataSortedFilter["id"];
        }


        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ], 200);
    }
    public function quoteExpiry(Request $request)
    {
        // return $request->input;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [
            'input' => 'required',
            'input.created_at' => 'required|date_format:Y-m-d|before_or_equal:today',
            'input.current_date' => 'required|date_format:Y-m-d',
            'input.valid_days' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }

        // return ($request->input["created_at"]);
        // $Today = date('y:m:d');

        // dd($Today);
        $createdAt = new DateTime($request->input["created_at"]);
        $currentDate = new DateTime($request->input["current_date"]);


        $expiryDate = new DateTime($request->input["created_at"]);
        $expiryDate->modify('+' . $request->input["valid_days"] . ' days');

        if ($currentDate >= $createdAt && $currentDate <= $expiryDate) {

            return response()->json([
                'success' => true,
                'data' => ["Valid" => true],
                'error' => null,
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'data' => ["Valid" => false],
                'error' => null,
            ], 200);
        }
    }
    public function productVisibility(Request $request)
    {
        // return $request->input;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [

            'input' => 'required|array',
            'input.customer.tags' => 'required|array',
            'input.customer.tags.*' => 'in:vip,wholesale,guest',
            'input.products' => 'required|array',
            'input.products.*.allow' => 'array',
            'input.products.*.allow.*' => 'in:vip,wholesale,guest',
            'input.products.*.block' => 'array',
            'input.products.*.block.*' => 'in:vip,wholesale,guest',

            // 'input.created_at' => 'required|date_format:Y-m-d|before_or_equal:today',
            // 'input.current_date' => 'required|date_format:Y-m-d',
            'input.products.*.id' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }
        $customerTags = $request->input['customer']['tags'];
        $products = $request->input['products'];

        $data = [];

        foreach ($products as $product) {

            $isVisible = true;

            if (!empty($product['allow'])) {
                $isVisible = false;

                foreach ($customerTags as $tag) {
                    if (in_array($tag, $product['allow'])) {
                        $isVisible = true;
                        break;
                    }
                }
            }

            if (!empty($product['block'])) {
                foreach ($customerTags as $tag) {
                    if (in_array($tag, $product['block'])) {
                        $isVisible = false;
                        break;
                    }
                }
            }
            if ($isVisible) {
                $data[] = $product['id'];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ], 200);
    }
    public function bundelPricing(Request $request)
    {
        // return $request->input;
        if (!$request->input) {

            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [

            'input' => 'required|array',


            'input.apply_bundle' =>  'required|boolean',
            'input.bundle_price' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_float($value) && !is_int($value)) {
                        $fail("The $attribute must be a .number");
                    }
                },
            ],
            'input.items' => 'required|array',
            'input.items.*.id' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.items.*.price' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_float($value) && !is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }


        $clculatedPrice = 0;
        $totalProductPrice = 0;
        foreach ($request->input['items'] as $product) {
            $totalProductPrice = $totalProductPrice + $product['price'];
        }
        // return $request->input['bundle_price'];
        if ($request->input['bundle_price'] <= $totalProductPrice && $request->input['apply_bundle']) {
            $clculatedPrice = $request->input['bundle_price'];
        } else {
            $clculatedPrice = $totalProductPrice;
        }
        return response()->json([
            'success' => true,
            'data' => ["final_price" => $clculatedPrice],
            'error' => null,
        ], 200);
    }
    public function cartMerge(Request $request)
    {
        // return "cart merge";
        if (!$request->input) {


            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [

            'input' => 'required|array',

            'input.guest' => 'array',
            'input.guest.*.id' => [
                'required',
                'numeric',
                'distinct',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.guest.*.qty' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.user' => 'array',
            'input.user.*.id' => [
                'required',
                'numeric',
                'distinct',
                // 'unique:user_cart,id',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.user.*.qty' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],

        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }


        $guestCart = $request->input('input.guest', []);
        $userCart  = $request->input('input.user', []);

        $mergedCart = [];

        foreach ($guestCart as $item) {
            $mergedCart[$item['id']] = $item['qty'];
        }

        foreach ($userCart as $item) {
            if (isset($mergedCart[$item['id']])) {
                $mergedCart[$item['id']] += $item['qty'];
            } else {
                $mergedCart[$item['id']] = $item['qty'];
            }
        }

        $finalCart = [];
        foreach ($mergedCart as $id => $qty) {
            $finalCart[] = [
                'id' => $id,
                'qty' => $qty
            ];
        }

        return response()->json([
            'success' => true,
            'data' =>  $finalCart,

            'error' => null,
        ], 200);
    }
    public function upsell(Request $request)
    {
        // return "cart merge";
        if (!$request->input) {


            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [

            'input' => 'required|array',
            'input.target' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.nums' => 'array',
            'input.nums.*' => [
                'required',
                'numeric',
                'distinct',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],




        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }


        $numes = $request->input('input.nums', []);
        $target  = $request->input('input.target');

        $output = [];

        foreach ($numes as $index => $num) {

            if (isset($numes[$index + 1]) && $num + $numes[$index + 1] == $target) {
                return response()->json([
                    'success' => true,
                    'data' =>  [$index, $index + 1],

                    'error' => null,
                ], 200);
            }
        }
        foreach ($numes as $index => $num) {
            // $output[] = $index;
            foreach ($numes as $ind => $numer) {
                if ($ind <= $index) {
                    continue;
                }
                if ($index != $ind && $num + $numer == $target) {
                    return response()->json([
                        'success' => true,
                        'data' => [$index, $ind],
                        'error' => null,
                    ], 200);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' =>  $output,

            'error' => null,
        ], 200);
    }
    public function shippingRule(Request $request)
    {
        // return $request;
        if (!$request->input) {


            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [

            'input' => 'required|array',
            'input.order' => 'required',
            'input.order.weight' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_float($value) && !is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.order.country' => [
                // 'required',
                'string',


            ],
            'input.order.method' => [
                // 'required',
                'string',


            ],
            'input.rules' => 'array',
            'input.rules.*.id' => [
                'required',
                'numeric',
                'distinct',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.rules.*.max_weight' => [
                // 'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.rules.*.method' => [
                'required',
                'string'
            ],
            'input.rules.*.country' => [

                'string'
            ],
            'input.rules.*.priority' => [
                'required',
                'numeric',
                'distinct',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],




        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }

        $order = $request->input['order'];
        $rules = $request->input['rules'];

        $matchedRules = [];

        foreach ($rules as $rule) {

            $isMatch = true;

            if (isset($rule['max_weight'])) {
                if ($order['weight'] > $rule['max_weight']) {
                    $isMatch = false;
                }
            }

            if (isset($rule['country'])) {
                if ($order['country'] != $rule['country']) {
                    $isMatch = false;
                }
            }
            // if (isset($rule['method'])) {
            //     if ($order['method'] != $rule['method']) {
            //         $isMatch = false;
            //     }
            // }

            if ($isMatch) {
                $matchedRules[] = $rule;
            }
        }

        if (empty($matchedRules)) {
            return response()->json([
                'success' => false,
                'message' => 'No matching shipping rule found',
                'data' => null
            ], 200);
        }

        $selectedRule = $matchedRules[0];

        foreach ($matchedRules as $rule) {
            if ($rule['priority'] < $selectedRule['priority']) {
                $selectedRule = $rule;
            }
        }


        return response()->json([
            'success' => true,
            'data' => $selectedRule,
            // 'data2' => $matchedRules,

            'error' => null
        ], 200);
    }
    public function fraudCheck(Request $request)
    {
        // return $request;
        if (!$request->input) {


            return response()->json([

                "success" => false,
                "error" => true,
                "message" => "Data is not correct check it",

            ], 201);
        }



        $validator = validator($request->all(), [

            'input' => 'required|array',
            'input.order' => 'required',
            'input.order.amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_float($value) && !is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.order.previous_orders' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.order.country' => [
                // 'required',
                'string',


            ],

            // 'input.rules' => 'array',
            'input.rules.max_amount' => [
                'required',
                'numeric',
                'distinct',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!is_float($value) && !is_int($value)) {
                        $fail("The $attribute must be a literal integer.");
                    }
                },
            ],
            'input.rules.blocked_countries' => [
                // 'required',
                'array',

            ],




        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200);
        }

        $order = $request->input['order'];
        $rules = $request->input['rules'];

        $matchedRules = [];


        foreach ($rules as $rule) {
            $isMatch = true;

            if ($order['amount'] > $request->input['rules']['max_amount']) {

                $isMatch = false;
            }


            if (in_array($order['country'], $request->input['rules']['blocked_countries'])) {
                $isMatch = false;
            }
            if ($isMatch) {
                 $matchedRules[] = $rule;
            }
        }

        if (empty($matchedRules)) {

            return response()->json([
               "success" => false,
                "error" => true,
                "message" => "Fraud detected for this order",
            ], 200);
        }else{
            return response()->json([
                'success' => true,
                'message' => 'No fraud found found',
                'data' => null
            ], 200);
        }


    }
    public function productPriceEngine(Request $request)
{
    if (!$request->input) {

        return response()->json([

            "success" => false,
            "error" => true,
            "message" => "Data is not correct check it",

        ], 201);
    }

    $validator = validator($request->all(), [

        'input' => 'required|array',

        'input.prices' => 'required|array',
        'input.prices.*' => 'required|array',

        'input.prices.*.*' => [
            'required',
            'numeric',
            function ($attribute, $value, $fail) {
                if (!is_int($value) && !is_float($value)) {
                    $fail("The $attribute must be a literal number.");
                }
            },
        ],

        'input.adjustment_value' => [
            'required',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                if (!is_int($value)) {
                    $fail("The $attribute must be a literal integer.");
                }
            },
        ],

    ]);

    if ($validator->fails()) {

        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 200);
    }

    $prices = $request->input['prices'];
    $x = $request->input['adjustment_value'];

    $flatPrices = [];

    foreach ($prices as $row) {

        foreach ($row as $price) {

            $flatPrices[] = $price;
        }
    }

    $base = $flatPrices[0];

    foreach ($flatPrices as $price) {

        if (($price - $base) % $x != 0) {

            return response()->json([
                "success" => true,
                "data" => [
                    "minimum_operations" => -1
                ]
            ], 200);
        }
    }

    sort($flatPrices);

    $middleIndex = floor(count($flatPrices) / 2);

    $target = $flatPrices[$middleIndex];

    $operations = 0;

    foreach ($flatPrices as $price) {

        $operations += abs($price - $target) / $x;
    }

    return response()->json([
        "success" => true,
        "data" => [
            "minimum_operations" => $operations
        ]
    ], 200);
}
public function dataSync(Request $request)
{
    if (!$request->input) {

        return response()->json([

            "success" => false,
            "error" => true,
            "message" => "Data is not correct check it",

        ], 201);
    }

    $validator = validator($request->all(), [

        'input' => 'required|array',

        'input.shopify' => 'required|array',
        'input.internal' => 'required|array',

        'input.shopify.price' => [
            'required',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                if (!is_int($value) && !is_float($value)) {
                    $fail("The $attribute must be a literal number.");
                }
            },
        ],

        'input.shopify.updated_at' => [
            'required',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                if (!is_int($value)) {
                    $fail("The $attribute must be a literal integer.");
                }
            },
        ],

        'input.internal.price' => [
            'required',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                if (!is_int($value) && !is_float($value)) {
                    $fail("The $attribute must be a literal number.");
                }
            },
        ],

        'input.internal.updated_at' => [
            'required',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                if (!is_int($value)) {
                    $fail("The $attribute must be a literal integer.");
                }
            },
        ],

    ]);

    if ($validator->fails()) {

        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 200);
    }

    $shopify = $request->input['shopify'];
    $internal = $request->input['internal'];

    $finalData = [];

    foreach ($request->input as $key => $value) {

        if ($shopify['updated_at'] > $internal['updated_at']) {

            $finalData = $shopify;

        } else {

            $finalData = $internal;
        }
    }

    return response()->json([

        "success" => true,
        "data" => [
            "final_resolved_value" => $finalData
        ]

    ], 200);
}
public function variantControl(Request $request)
{
    if (!$request->input) {

        return response()->json([

            "success" => false,
            "error" => true,
            "message" => "Data is not correct check it",

        ], 201);
    }

    $validator = validator($request->all(), [

        'input' => 'required|array',

        'input.options' => 'required|array',

        'input.options.*.name' => [
            'required',
            'string',
        ],

        'input.options.*.values' => [
            'required',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                if (!is_int($value)) {
                    $fail("The $attribute must be a literal integer.");
                }
            },
        ],

        'input.limit' => [
            'required',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                if (!is_int($value)) {
                    $fail("The $attribute must be a literal integer.");
                }
            },
        ],

    ]);

    if ($validator->fails()) {

        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 200);
    }

    $options = $request->input['options'];
    $limit = $request->input['limit'];

    $totalCombinations = 1;

    foreach ($options as $option) {

        $totalCombinations *= $option['values'];
    }

    // $isExceeded = false;

    // if ($totalCombinations > $limit) {

    //     $isExceeded = true;
    // }

    return response()->json([

        "success" => true,

        "data" => [

            "result" => $totalCombinations,
            // "limit_exceeded" => $isExceeded

        ]

    ], 200);
}
public function orderState(Request $request)
{
    if (!$request->input) {

        return response()->json([

            "success" => false,
            "error" => true,
            "message" => "Data is not correct check it",

        ], 201);
    }

    $validator = validator($request->all(), [

        'input' => 'required|array',

        'input.transitions' => 'required|array|min:1',

        'input.transitions.*' => 'required|string',

    ]);

    if ($validator->fails()) {

        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 200);
    }

    $transitions = $request->input['transitions'];

    $allowedFlow = [
        'created',
        'paid',
        'processing',
        'shipped',
        'delivered'
    ];

    $isValid = true;

    foreach ($transitions as $index => $state) {

        if (!in_array($state, $allowedFlow)) {
            $isValid = false;
            break;
        }

        if ($index > 0) {

            $previousState = $transitions[$index - 1];

            $previousPosition = array_search($previousState, $allowedFlow);
            $currentPosition = array_search($state, $allowedFlow);

            if ($currentPosition != $previousPosition + 1) {
                $isValid = false;
                break;
            }
        }
    }

    return response()->json([

        "success" => true,

        "data" => [

            "valid" => $isValid

        ]

    ], 200);
}

}
