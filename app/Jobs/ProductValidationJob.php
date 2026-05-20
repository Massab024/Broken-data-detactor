<?php

namespace App\Jobs;

use Throwable;
use App\Models\AppActivityLog;
use App\Services\ProductValidationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProductValidationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId)
    {
    }

    public function handle(ProductValidationService $validationService): void
    {
        Log::info('ProductValidationJob started', [
            'user_id' => $this->userId,
        ]);

        AppActivityLog::create([
            'user_id' => $this->userId,
            'event' => 'product_validation_started',
            'level' => 'info',
            'message' => 'Product validation started',
            'details' => [
                'user_id' => $this->userId,
            ],
        ]);

        try {
            $summary = $validationService->validateForUser($this->userId);

            AppActivityLog::create([
                'user_id' => $this->userId,
                'event' => 'product_validation_completed',
                'level' => 'info',
                'message' => 'Product validation completed',
                'details' => [
                    'user_id' => $this->userId,
                    'products_checked' => $summary['products_checked'],
                    'issues_detected' => $summary['issues_detected'],
                    'issues_resolved' => $summary['issues_resolved'],
                ],
            ]);
        } catch (Throwable $throwable) {
            Log::error('ProductValidationJob failed', [
                'user_id' => $this->userId,
                'error' => $throwable->getMessage(),
            ]);

            AppActivityLog::create([
                'user_id' => $this->userId,
                'event' => 'product_validation_failed',
                'level' => 'error',
                'message' => 'Product validation failed',
                'details' => [
                    'user_id' => $this->userId,
                    'error' => $throwable->getMessage(),
                ],
            ]);

            throw $throwable;
        }
    }
}
