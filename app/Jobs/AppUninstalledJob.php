<?php

namespace App\Jobs;

use stdClass;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Osiset\ShopifyApp\Contracts\Commands\Shop;
use Osiset\ShopifyApp\Actions\CancelCurrentPlan;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Contracts\Queries\Shop as QueriesShop;
use App\Services\ShopifyStoreCleanupService;
use Illuminate\Support\Facades\Log;

class AppUninstalledJob extends \Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's myshopify domain
     *
     * @var ShopDomain|string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Shop $shopCommand, QueriesShop $shopQuery, CancelCurrentPlan $cancelCurrentPlanAction): bool
    {
        $this->shopDomain = ShopDomain::fromNative($this->shopDomain);

        $shop = $shopQuery->getByDomain($this->shopDomain);

        Log::info('App uninstall webhook received.', [
            'shop_domain' => $this->shopDomain->toNative(),
            'shop_name' => $shop?->name,
        ]);

        app()->make(\App\Services\ShopifyStoreCleanupService::class)->cleanupForShopDomain($this->shopDomain->toNative());

        Log::info('App uninstalled for shop: ' . $this->shopDomain->toNative());
        return true;
    }
}
