<?php

namespace App\Jobs;

use App\Models\AppActivityLog;
use App\Models\User;
use App\Http\Traits\ResponseTrait;
use App\Http\Traits\ShopifyProductTrait;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Repositories\Product\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ProductSyncJob implements ShouldQueue
{
    use Queueable, ShopifyProductTrait, ResponseTrait;

    public function __construct(public int $userId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProductSyncJob started', [
            'user_id' => $this->userId,
        ]);

        $this->getProductRepository(app(ProductRepositoryInterface::class));
        $user = User::query()->findOrFail($this->userId);

        if (!$user) {
            return;
        }

        $this->writeActivityLog(
            'product_sync_started',
            'info',
            'Product sync started',
            ['user_id' => $this->userId]
        );

        if ($this->getProductsFromShopify($user)) {
            $this->writeActivityLog(
                'product_sync_completed',
                'info',
                'Product sync completed',
                ['user_id' => $this->userId]
            );

            $this->logInfo('Products Synced successfully from Shopify');
        } else {
            $this->writeActivityLog(
                'product_sync_failed',
                'error',
                'Product sync failed',
                ['user_id' => $this->userId]
            );

            $this->logInfo('Products Synced failed from Shopify');
        }
    }

    protected function writeActivityLog(string $event, string $level, string $message, array $details = []): void
    {
        AppActivityLog::create([
            'user_id' => $this->userId,
            'event' => $event,
            'level' => $level,
            'message' => $message,
            'details' => $details,
        ]);
    }
}
