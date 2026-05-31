<?php

namespace App\Http\Controllers;

use App\Models\Products\Product;
use App\Models\Products\ProductIssue;
use App\Models\Products\ProductVarient;
use App\Models\ValidationRule;
use Illuminate\Http\Request;

class ProductIssueController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()?->id;

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'severity' => (string) $request->query('severity', 'all'),
            'status' => (string) $request->query('status', 'all'),
            'issue_key' => (string) $request->query('issue_key', 'all'),
            'health_status' => (string) $request->query('health_status', 'all'),
            'issue_id' => $request->query('issue_id'),
        ];

        $query = ProductIssue::query()
            ->with(['product:id,title,health_status,last_checked_at'])
            ->whereHas('product', fn ($productQuery) => $productQuery->where('user_id', $userId))
            ->when($filters['search'] !== '', function ($builder) use ($filters) {
                $builder->whereHas('product', function ($productQuery) use ($filters) {
                    $productQuery->where('title', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->when($filters['severity'] !== 'all', function ($builder) use ($filters) {
                $builder->where('severity', $filters['severity']);
            })
            ->when($filters['status'] !== 'all', function ($builder) use ($filters) {
                if ($filters['status'] === 'open') {
                    $builder->whereNull('resolved_at');
                    return;
                }

                if ($filters['status'] === 'resolved') {
                    $builder->whereNotNull('resolved_at');
                }
            })
            ->when($filters['issue_key'] !== 'all', function ($builder) use ($filters) {
                $builder->where('issue_key', $filters['issue_key']);
            })
            ->when($filters['health_status'] !== 'all', function ($builder) use ($filters) {
                $builder->whereHas('product', function ($productQuery) use ($filters) {
                    $productQuery->where('health_status', $filters['health_status']);
                });
            })
            ->orderByDesc('created_at');

        $issues = $query->paginate(10)->withQueryString();
        $issues->getCollection()->transform(function (ProductIssue $issue) {
            return [
                'id' => $issue->id,
                'product' => [
                    'id' => $issue->product?->id,
                    'title' => $issue->product?->title ?? 'Unknown Product',
                    'health_status' => $issue->product?->health_status ?? 'unknown',
                    'last_checked_at' => optional($issue->product?->last_checked_at)->toDateTimeString(),
                ],
                'issue_key' => $issue->issue_key,
                'severity' => $issue->severity,
                'message' => $issue->message,
                'status' => $issue->resolved_at ? 'resolved' : 'open',
                'detected_at' => optional($issue->detected_at ?? $issue->created_at)?->toDateTimeString(),
                'last_checked_at' => optional($issue->product?->last_checked_at)?->toDateTimeString(),
                'resolved_at' => optional($issue->resolved_at)?->toDateTimeString(),
                'metadata' => $issue->metadata,
            ];
        });

        $validationRules = ValidationRule::query()
            ->select(['rule_key', 'name'])
            ->where('is_enabled', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ValidationRule $rule) => [
                'label' => $rule->name,
                'value' => $rule->rule_key,
            ])
            ->values();

        $selectedIssue = null;
        if (!empty($filters['issue_id'])) {
            $issue = ProductIssue::query()
                ->with(['product:id,title,health_status,last_checked_at'])
                ->whereHas('product', fn ($productQuery) => $productQuery->where('user_id', $userId))
                ->find($filters['issue_id']);

            if ($issue) {
                $selectedIssue = [
                    'id' => $issue->id,
                    'product' => [
                        'id' => $issue->product?->id,
                        'title' => $issue->product?->title ?? 'Unknown Product',
                        'health_status' => $issue->product?->health_status ?? 'unknown',
                        'last_checked_at' => optional($issue->product?->last_checked_at)->toDateTimeString(),
                    ],
                    'issue_key' => $issue->issue_key,
                    'severity' => $issue->severity,
                    'message' => $issue->message,
                    'status' => $issue->resolved_at ? 'resolved' : 'open',
                    'detected_at' => optional($issue->detected_at ?? $issue->created_at)?->toDateTimeString(),
                    'last_checked_at' => optional($issue->product?->last_checked_at)?->toDateTimeString(),
                    'resolved_at' => optional($issue->resolved_at)?->toDateTimeString(),
                    'metadata' => $issue->metadata,
                ];
            }
        }

        return $this->render('ProductIssues', [
            'issues' => $issues,
            'filters' => $filters,
            'filter_options' => [
                'severities' => [
                    ['label' => 'All severities', 'value' => 'all'],
                    ['label' => 'Critical', 'value' => 'critical'],
                    ['label' => 'High', 'value' => 'high'],
                    ['label' => 'Medium', 'value' => 'medium'],
                    ['label' => 'Low', 'value' => 'low'],
                ],
                'statuses' => [
                    ['label' => 'All statuses', 'value' => 'all'],
                    ['label' => 'Open', 'value' => 'open'],
                    ['label' => 'Resolved', 'value' => 'resolved'],
                ],
                'health_statuses' => [
                    ['label' => 'All health statuses', 'value' => 'all'],
                    ['label' => 'Healthy', 'value' => 'healthy'],
                    ['label' => 'Warning', 'value' => 'warning'],
                    ['label' => 'Critical', 'value' => 'critical'],
                    ['label' => 'Needs Review', 'value' => 'needs_review'],
                ],
                'issue_types' => $validationRules,
            ],
            'selected_issue' => $selectedIssue,
        ]);
    }

    public function show(Request $request, ProductIssue $productIssue)
    {
        $productIssue->loadMissing(['product.user', 'product.productVarients']);

        $product = $productIssue->product;

        abort_unless($product instanceof Product, 404);
        abort_unless($product->user_id === $request->user()?->id, 404);

        $allIssues = ProductIssue::query()
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->get();

        $validationRule = ValidationRule::query()
            ->where('rule_key', $productIssue->issue_key)
            ->first();

        return $this->render('ProductIssueShow', [
            'product_issue' => $this->mapIssue($productIssue),
            'product' => $this->mapProduct($product),
            'variants' => $product->productVarients->map(fn (ProductVarient $variant) => $this->mapVariant($variant))->values(),
            'open_issues' => $allIssues
                ->filter(fn (ProductIssue $issue) => $issue->resolved_at === null)
                ->map(fn (ProductIssue $issue) => $this->mapIssue($issue))
                ->values(),
            'resolved_issues' => $allIssues
                ->filter(fn (ProductIssue $issue) => $issue->resolved_at !== null)
                ->map(fn (ProductIssue $issue) => $this->mapIssue($issue))
                ->values(),
            'validation_rule' => $validationRule ? [
                'rule_key' => $validationRule->rule_key,
                'name' => $validationRule->name,
                'description' => $validationRule->description,
                'severity' => $validationRule->severity,
                'is_enabled' => $validationRule->is_enabled,
                'config' => $validationRule->config,
            ] : null,
            'shopify_admin_url' => $this->buildShopifyAdminProductUrl($product),
        ]);
    }

    protected function mapIssue(ProductIssue $issue): array
    {
        return [
            'id' => $issue->id,
            'product_id' => $issue->product_id,
            'shopify_product_id' => $issue->shopify_product_id,
            'issue_key' => $issue->issue_key,
            'severity' => $issue->severity,
            'message' => $issue->message,
            'status' => $issue->resolved_at ? 'resolved' : 'open',
            'detected_at' => optional($issue->detected_at ?? $issue->created_at)?->toDateTimeString(),
            'resolved_at' => optional($issue->resolved_at)?->toDateTimeString(),
            'suggested_fix' => $issue->suggested_fix ?? ($issue->metadata['suggested_fix'] ?? null),
            'metadata' => $issue->metadata,
        ];
    }

    protected function mapProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'shopify_product_id' => $product->shopify_product_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'status' => $product->status,
            'health_status' => $product->health_status,
            'image_url' => $product->image_url,
            'last_synced_at' => optional($product->last_synced_at)?->toDateTimeString(),
            'last_checked_at' => optional($product->last_checked_at)?->toDateTimeString(),
        ];
    }

    protected function mapVariant(ProductVarient $variant): array
    {
        return [
            'id' => $variant->id,
            'title' => $variant->title,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'inventory_quantity' => $variant->inventory_quantity,
            'shopify_variant_id' => $variant->shopify_variant_id,
        ];
    }

    protected function buildShopifyAdminProductUrl(Product $product): string
    {
        $shopDomain = (string) ($product->user?->getDomain()?->toNative() ?? $product->user?->name ?? '');
        $shopDomain = strtolower(trim($shopDomain));
        $shopDomain = preg_replace('/^https?:\/\//', '', $shopDomain) ?? $shopDomain;
        $shopDomain = rtrim($shopDomain, '/');

        if ($shopDomain === '') {
            return '#';
        }

        $storeHandle = preg_replace('/\.myshopify\.com$/', '', $shopDomain) ?: $shopDomain;

        return 'https://admin.shopify.com/store/' . rawurlencode($storeHandle) . '/products/' . rawurlencode((string) $product->shopify_product_id);
    }
}
