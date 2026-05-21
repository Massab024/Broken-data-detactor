<?php

namespace App\Services;

use App\Models\AppActivityLog;
use App\Models\Orders\Order;
use App\Models\Orders\OrderCustomer;
use App\Models\Orders\OrderFulfillment;
use App\Models\Orders\OrderLineItem;
use App\Models\Orders\OrderShippingAddress;
use App\Models\Products\Product;
use App\Models\Products\ProductIssue;
use App\Models\Products\ProductMedia;
use App\Models\Products\ProductVarient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopifyStoreCleanupService
{
    public function cleanupForShopDomain(string $shopDomain): void
    {
        $normalizedShopDomain = $this->normalizeShopDomain($shopDomain);

        $user = User::query()
            ->where('name', $normalizedShopDomain)
            ->first();

        if (!$user) {
            Log::warning('Shopify uninstall cleanup skipped; store user not found.', [
                'shop_domain' => $normalizedShopDomain,
            ]);

            return;
        }

        $this->cleanupForUser($user);
    }

    public function cleanupForUser(User $user): void
    {
        $productIds = Product::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $shopifyProductIds = Product::query()
            ->where('user_id', $user->id)
            ->pluck('shopify_product_id')
            ->filter()
            ->values();

        $orderIds = Order::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $orderCustomerIds = Order::query()
            ->where('user_id', $user->id)
            ->whereNotNull('order_customer_id')
            ->distinct()
            ->pluck('order_customer_id')
            ->filter()
            ->values();

        $shopDomain = (string) $user->name;

        Log::info('Shopify uninstall cleanup started.', [
            'user_id' => $user->id,
            'shop_domain' => $shopDomain,
            'product_count' => $productIds->count(),
            'order_count' => $orderIds->count(),
        ]);

        try {
            DB::transaction(function () use ($user, $productIds, $shopifyProductIds, $orderIds, $orderCustomerIds, $shopDomain): void {
                if ($productIds->isNotEmpty()) {
                    DB::table('product_issues')->whereIn('product_id', $productIds)->delete();
                    DB::table('product_varients')->whereIn('product_id', $productIds)->delete();
                    DB::table('product_media')->whereIn('product_id', $productIds)->delete();
                }

                if ($orderIds->isNotEmpty()) {
                    DB::table('order_line_items')->whereIn('order_id', $orderIds)->delete();
                    DB::table('order_fulfillments')->whereIn('order_id', $orderIds)->delete();
                    DB::table('order_shipping_addresses')->whereIn('order_id', $orderIds)->delete();
                }

                if ($productIds->isNotEmpty()) {
                    DB::table('products')->where('user_id', $user->id)->delete();
                }

                if ($orderIds->isNotEmpty()) {
                    DB::table('orders')->where('user_id', $user->id)->delete();
                }

                if ($orderCustomerIds->isNotEmpty()) {
                    DB::table('order_customers')->whereIn('id', $orderCustomerIds)->delete();
                }

                AppActivityLog::query()->where('user_id', $user->id)->delete();

                $this->deleteWebhookLogs($shopDomain, $shopifyProductIds, $orderIds);

                $user->forceDelete();
            });

            Log::info('Shopify uninstall cleanup completed.', [
                'user_id' => $user->id,
                'shop_domain' => $shopDomain,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Shopify uninstall cleanup failed.', [
                'user_id' => $user->id,
                'shop_domain' => $shopDomain,
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    protected function deleteWebhookLogs(string $shopDomain, $shopifyProductIds, $orderIds): void
    {
        $webhookLogQuery = DB::table('webhook_logs');

        $webhookLogQuery->where(function ($query) use ($shopDomain, $shopifyProductIds, $orderIds): void {
            if ($shopifyProductIds->isNotEmpty()) {
                $query->whereIn('shopify_product_id', $shopifyProductIds);
            }

            if ($orderIds->isNotEmpty()) {
                $query->orWhereIn('shopify_order_id', $orderIds);
            }

            $query->orWhereRaw('LOWER(CAST(request_payload AS CHAR)) LIKE ?', ['%' . strtolower($shopDomain) . '%'])
                ->orWhereRaw('LOWER(CAST(response_payload AS CHAR)) LIKE ?', ['%' . strtolower($shopDomain) . '%'])
                ->orWhereRaw('LOWER(CAST(message AS CHAR)) LIKE ?', ['%' . strtolower($shopDomain) . '%']);
        })->delete();
    }

    protected function normalizeShopDomain(string $shopDomain): string
    {
        $normalized = strtolower(trim($shopDomain));
        $normalized = preg_replace('/^https?:\/\//', '', $normalized) ?? $normalized;

        return rtrim($normalized, '/');
    }
}
