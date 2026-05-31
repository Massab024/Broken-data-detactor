<?php

namespace App\Services;

use App\Models\AppActivityLog;
use App\Models\Products\Product;
use App\Models\Products\ProductIssue;
use App\Models\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductValidationService
{
    public function validateForUser(int $userId): array
    {
        $rules = $this->enabledRules();
        $products = Product::query()
            ->where('user_id', $userId)
            ->with('productVarients')
            ->get();

        Log::info('Validation scope loaded', [
            'user_id' => $userId,
            'enabled_rules_count' => $rules->count(),
            'products_count' => $products->count(),
        ]);

        $summary = [
            'products_checked' => 0,
            'issues_detected' => 0,
            'issues_resolved' => 0,
            'products' => [],
        ];

        foreach ($products as $product) {
            Log::info('Checking product', [
                'user_id' => $userId,
                'product_id' => $product->id,
                'shopify_product_id' => $product->shopify_product_id,
                'title' => $product->title,
            ]);

            $result = $this->validateProduct($product, $userId, $rules);

            $summary['products_checked']++;
            $summary['issues_detected'] += $result['issues_detected'];
            $summary['issues_resolved'] += $result['issues_resolved'];
            $summary['products'][] = $result;
        }

        Log::info('Validation completed', [
            'user_id' => $userId,
            'products_checked' => $summary['products_checked'],
            'issues_detected' => $summary['issues_detected'],
            'issues_resolved' => $summary['issues_resolved'],
        ]);

        return $summary;
    }

    public function validateAllProducts(?int $userId = null): array
    {
        if ($userId !== null) {
            return $this->validateForUser($userId);
        }

        $rules = $this->enabledRules();
        $products = Product::query()->with('productVarients')->get();

        $summary = [
            'products_checked' => 0,
            'issues_detected' => 0,
            'issues_resolved' => 0,
            'products' => [],
        ];

        foreach ($products as $product) {
            $result = $this->validateProduct($product, $userId, $rules);

            $summary['products_checked']++;
            $summary['issues_detected'] += $result['issues_detected'];
            $summary['issues_resolved'] += $result['issues_resolved'];
            $summary['products'][] = $result;
        }

        return $summary;
    }

    public function validateProduct(Product $product, ?int $userId = null, ?Collection $rules = null): array
    {
        return DB::transaction(function () use ($product, $userId, $rules) {
            $product->loadMissing('productVarients');
            $previousHealthStatus = $product->health_status;

            $rules ??= $this->enabledRules();
            $openIssues = collect();
            $detectedCount = 0;
            $resolvedCount = 0;

            foreach ($rules as $rule) {
                $ruleResult = $this->evaluateRule($product, $rule, $userId);
                $openIssues = $openIssues->merge($ruleResult['open_issues']);
                $detectedCount += $ruleResult['detected_count'];
                $resolvedCount += $ruleResult['resolved_count'];
            }

            $healthStatus = $this->resolveHealthStatus($openIssues);

            $product->forceFill([
                'health_status' => $healthStatus,
                'last_checked_at' => now(),
            ])->save();

            $logUserId = $userId ?? $product->user_id;
            if ($logUserId !== null && $previousHealthStatus !== $healthStatus && in_array($healthStatus, ['needs_review', 'healthy'], true)) {
                $event = $healthStatus === 'needs_review' ? 'product_health_needs_review' : 'product_health_healthy';
                $message = $healthStatus === 'needs_review'
                    ? 'Product health status changed to needs review.'
                    : 'Product health status changed to healthy.';

                $this->writeActivityLog(
                    $event,
                    'info',
                    $message,
                    [
                        'product_id' => $product->id,
                        'shopify_product_id' => $product->shopify_product_id,
                        'previous_health_status' => $previousHealthStatus,
                        'current_health_status' => $healthStatus,
                    ],
                    (int) $logUserId
                );
            }

            return [
                'product_id' => $product->id,
                'shopify_product_id' => $product->shopify_product_id,
                'health_status' => $healthStatus,
                'issues_detected' => $detectedCount,
                'issues_resolved' => $resolvedCount,
                'open_issues' => $openIssues->values()->all(),
            ];
        });
    }

    protected function enabledRules(): Collection
    {
        return ValidationRule::query()
            ->where('is_enabled', true)
            ->orderBy('id')
            ->get();
    }

    protected function evaluateRule(Product $product, ValidationRule $rule, ?int $userId = null): array
    {
        $result = $this->buildRuleResult($product, $rule);
        $logUserId = $userId ?? $product->user_id;
        $openIssues = ProductIssue::query()
            ->where('product_id', $product->id)
            ->where('issue_key', $rule->rule_key)
            ->open()
            ->get();

        if ($result['triggered']) {
            if ($openIssues->isEmpty()) {
                $issue = ProductIssue::create([
                    'product_id' => $product->id,
                    'shopify_product_id' => $product->shopify_product_id,
                    'issue_key' => $rule->rule_key,
                    'issue_type' => 'validation',
                    'severity' => $rule->severity,
                    'status' => 'open',
                    'message' => $result['message'],
                    'suggested_fix' => $result['suggested_fix'],
                    'detected_at' => now(),
                    'metadata' => [
                        'suggested_fix' => $result['suggested_fix'],
                        'rule_name' => $rule->name,
                    ],
                ]);

                if ($logUserId !== null) {
                    $this->writeActivityLog(
                        'product_issue_detected',
                        'warning',
                        'Issue detected',
                        [
                            'product_id' => $product->id,
                            'shopify_product_id' => $product->shopify_product_id,
                            'issue_key' => $rule->rule_key,
                            'severity' => $rule->severity,
                            'message' => $result['message'],
                        ],
                        (int) $logUserId
                    );

                    if (strtolower((string) $rule->severity) === 'low') {
                        $this->writeActivityLog(
                            'product_low_issue_detected',
                            'info',
                            'Low severity issue detected',
                            [
                                'product_id' => $product->id,
                                'shopify_product_id' => $product->shopify_product_id,
                                'issue_key' => $rule->rule_key,
                                'severity' => $rule->severity,
                                'message' => $result['message'],
                            ],
                            (int) $logUserId
                        );
                    }
                }

                Log::info('Issue created', [
                    'user_id' => $userId,
                    'product_id' => $product->id,
                    'issue_id' => $issue->id,
                    'issue_key' => $rule->rule_key,
                ]);

                return [
                    'detected_count' => 1,
                    'resolved_count' => 0,
                    'open_issues' => collect([$issue]),
                ];
            }

            $openIssues->each(function (ProductIssue $issue) use ($rule, $result): void {
                $issue->forceFill([
                    'severity' => $rule->severity,
                    'status' => 'open',
                    'message' => $result['message'],
                    'suggested_fix' => $result['suggested_fix'],
                    'metadata' => array_merge($issue->metadata ?? [], [
                        'suggested_fix' => $result['suggested_fix'],
                        'rule_name' => $rule->name,
                    ]),
                ])->save();
            });

            return [
                'detected_count' => 0,
                'resolved_count' => 0,
                'open_issues' => $openIssues,
            ];
        }

        if ($openIssues->isNotEmpty()) {
            $openIssues->each(function (ProductIssue $issue) use ($rule, $logUserId): void {
                $issue->forceFill([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ])->save();

                if ($logUserId !== null) {
                    $this->writeActivityLog(
                        'product_issue_resolved',
                        'info',
                        'Issue resolved',
                        [
                            'product_id' => $issue->product_id,
                            'shopify_product_id' => $issue->shopify_product_id,
                            'issue_key' => $issue->issue_key,
                            'severity' => $issue->severity,
                        ],
                        (int) $logUserId
                    );

                    if (strtolower((string) $rule->severity) === 'low') {
                        $this->writeActivityLog(
                            'product_low_issue_resolved',
                            'info',
                            'Low severity issue resolved',
                            [
                                'product_id' => $issue->product_id,
                                'shopify_product_id' => $issue->shopify_product_id,
                                'issue_key' => $issue->issue_key,
                                'severity' => $issue->severity,
                            ],
                            (int) $logUserId
                        );
                    }
                }

                Log::info('Issue resolved', [
                    'user_id' => $userId,
                    'product_id' => $issue->product_id,
                    'issue_id' => $issue->id,
                    'issue_key' => $issue->issue_key,
                ]);
            });

            return [
                'detected_count' => 0,
                'resolved_count' => $openIssues->count(),
                'open_issues' => collect(),
            ];
        }

        return [
            'detected_count' => 0,
            'resolved_count' => 0,
            'open_issues' => collect(),
        ];
    }

    protected function buildRuleResult(Product $product, ValidationRule $rule): array
    {
        return match ($rule->rule_key) {
            'missing_product_title' => $this->missingProductTitle($product),
            'invalid_product_price' => $this->invalidProductPrice($product),
            'product_has_no_variants' => $this->productHasNoVariants($product),
            'variant_missing_price' => $this->variantMissingPrice($product),
            'missing_product_image' => $this->missingProductImage($product),
            'missing_vendor' => $this->missingVendor($product),
            'missing_product_type' => $this->missingProductType($product),
            'missing_sku' => $this->missingSku($product),
            'product_status_draft' => $this->productStatusDraft($product),
            'weak_handle' => $this->weakHandle($product),
            default => [
                'triggered' => false,
                'message' => null,
                'suggested_fix' => null,
            ],
        };
    }

    protected function missingProductTitle(Product $product): array
    {
        $triggered = trim((string) $product->title) === '';

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product title is missing.' : null,
            'suggested_fix' => $triggered ? 'Add a clear product title in Shopify admin.' : null,
        ];
    }

    protected function invalidProductPrice(Product $product): array
    {
        $triggered = !$this->isValidPositivePrice($product->min_price);

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product price is missing or invalid.' : null,
            'suggested_fix' => $triggered ? 'Add a valid product price in Shopify admin.' : null,
        ];
    }

    protected function productHasNoVariants(Product $product): array
    {
        $variantCount = (int) ($product->variant_count ?? 0);
        $triggered = !$product->has_variants || $variantCount <= 0 || $product->productVarients->isEmpty();

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product has no variants.' : null,
            'suggested_fix' => $triggered ? 'Add at least one product variant in Shopify admin.' : null,
        ];
    }

    protected function variantMissingPrice(Product $product): array
    {
        $triggered = $product->productVarients->contains(function ($variant): bool {
            return !$this->isValidPositivePrice($variant->price);
        });

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'One or more variants have missing or invalid price.' : null,
            'suggested_fix' => $triggered ? 'Update all variant prices in Shopify admin.' : null,
        ];
    }

    protected function missingProductImage(Product $product): array
    {
        $triggered = trim((string) $product->image_url) === '';

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product image is missing.' : null,
            'suggested_fix' => $triggered ? 'Add a product image in Shopify admin.' : null,
        ];
    }

    protected function missingVendor(Product $product): array
    {
        $triggered = $this->isBlank($product->vendor);

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product vendor is missing.' : null,
            'suggested_fix' => $triggered ? 'Add a vendor to this product in Shopify admin.' : null,
        ];
    }

    protected function missingProductType(Product $product): array
    {
        $triggered = $this->isBlank($product->product_type);

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product type is missing.' : null,
            'suggested_fix' => $triggered ? 'Add a product type/category in Shopify admin.' : null,
        ];
    }

    protected function missingSku(Product $product): array
    {
        $triggered = $product->productVarients->contains(function ($variant): bool {
            return $this->isBlank($variant->sku);
        });

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'One or more variants are missing SKU.' : null,
            'suggested_fix' => $triggered ? 'Add SKU values to all product variants in Shopify admin.' : null,
        ];
    }

    protected function productStatusDraft(Product $product): array
    {
        $status = strtolower(trim((string) $product->status));
        $triggered = $status === 'draft';

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product is currently in draft status.' : null,
            'suggested_fix' => $triggered ? 'Review the product and publish it when ready.' : null,
        ];
    }

    protected function weakHandle(Product $product): array
    {
        $handle = trim((string) $product->handle);
        $triggered = $handle === ''
            || mb_strlen($handle) < 5
            || preg_match('/\s/', $handle)
            || preg_match('/[A-Z]/', $handle);

        return [
            'triggered' => $triggered,
            'message' => $triggered ? 'Product handle needs review.' : null,
            'suggested_fix' => $triggered ? 'Use a clean, lowercase, SEO-friendly product handle.' : null,
        ];
    }

    protected function isBlank(mixed $value): bool
    {
        return trim((string) $value) === '';
    }

    protected function isValidPositivePrice(mixed $price): bool
    {
        if ($price === null) {
            return false;
        }

        if (!is_numeric($price)) {
            return false;
        }

        return (float) $price > 0;
    }

    protected function resolveHealthStatus(Collection $openIssues): string
    {
        $severities = $openIssues
            ->pluck('severity')
            ->filter()
            ->map(fn ($severity) => strtolower((string) $severity));

        if ($severities->contains('critical')) {
            return 'critical';
        }

        if ($severities->contains(fn ($severity) => in_array($severity, ['high', 'medium'], true))) {
            return 'warning';
        }

        if ($severities->contains('low')) {
            return 'needs_review';
        }

        return 'healthy';
    }

    protected function writeActivityLog(string $event, string $level, string $message, array $details = [], ?int $userId = null): void
    {
        AppActivityLog::create([
            'user_id' => $userId,
            'event' => $event,
            'level' => $level,
            'message' => $message,
            'details' => $details,
        ]);
    }
}
