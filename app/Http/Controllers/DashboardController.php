<?php

namespace App\Http\Controllers;

use App\Jobs\ProductValidationJob;
use App\Jobs\ProductSyncJob;
use App\Models\AppActivityLog;
use App\Models\Products\Product;
use App\Models\Products\ProductIssue;
use App\Models\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;




class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->render('Dashboard', $this->dashboardData($request->user()?->id, $request->query()));
    }

    public function syncProducts(Request $request)
    {
        Log::info('Sync Products controller hit', [
            'user_id' => $request->user()?->id,
            'shop' => $request->user()?->name,
        ]);

        ProductSyncJob::dispatch($request->user()->id);

        return back()->with('success', 'Product sync has been queued.');
    }

    public function validateProducts(Request $request)
    {
        $user = $request->user();

        Log::info('Run Validation button clicked', [
            'user_id' => $user?->id,
            'shop' => $user?->name,
        ]);

        ProductValidationJob::dispatch($user->id);

        return back()->with('success', 'Product validation has been queued.');
    }

    protected function dashboardData(?int $userId, array $query = []): array
    {
        $products = Product::query()
            ->when($userId !== null, fn ($builder) => $builder->where('user_id', $userId))
            ->get();

        $healthCounts = Product::query()
            ->when($userId !== null, fn ($builder) => $builder->where('user_id', $userId))
            ->select('health_status', DB::raw('COUNT(*) as total'))
            ->groupBy('health_status')
            ->pluck('total', 'health_status')
            ->all();

        $severityCounts = ProductIssue::query()
            ->whereHas('product', fn ($productQuery) => $productQuery->when($userId !== null, fn ($builder) => $builder->where('user_id', $userId)))
            ->open()
            ->select('severity', DB::raw('COUNT(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->all();

        $recentIssues = ProductIssue::query()
            ->with(['product:id,title,health_status,last_checked_at'])
            ->whereHas('product', fn ($productQuery) => $productQuery->when($userId !== null, fn ($builder) => $builder->where('user_id', $userId)))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(ProductIssue $issue) => [
                'id' => $issue->id,
                'product' => [
                    'id' => $issue->product?->id,
                    'title' => $issue->product?->title ?? 'Unknown Product',
                ],
                'issue_key' => $issue->issue_key,
                'severity' => $issue->severity,
                'status' => $issue->resolved_at ? 'resolved' : 'open',
                'detected_at' => optional($issue->created_at)?->toDateTimeString(),
                'view_url' => route('product.issues.show', array_merge(['productIssue' => $issue->id], $query)),
            ])
            ->values();

        $recentActivityLogs = AppActivityLog::query()
            ->when($userId !== null, fn ($builder) => $builder->where('user_id', $userId))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(AppActivityLog $log) => [
                'type' => $log->level,
                'title' => Str::headline((string) $log->event),
                'message' => $log->message,
                'date' => optional($log->created_at)?->toDateTimeString(),
            ])
            ->values();

        return [
            'total_products_scanned' => $products->count(),
            'healthy_products_count' => $products->where('health_status', 'healthy')->count(),
            'warning_products_count' => $products->where('health_status', 'warning')->count(),
            'critical_products_count' => $products->where('health_status', 'critical')->count(),
            'needs_review_products_count' => $products->where('health_status', 'needs_review')->count(),
            'open_issues_count' => ProductIssue::query()
                ->whereHas('product', fn ($productQuery) => $productQuery->when($userId !== null, fn ($builder) => $builder->where('user_id', $userId)))
                ->whereNull('resolved_at')
                ->count(),
            'resolved_issues_count' => ProductIssue::query()
                ->whereHas('product', fn ($productQuery) => $productQuery->when($userId !== null, fn ($builder) => $builder->where('user_id', $userId)))
                ->whereNotNull('resolved_at')
                ->count(),
            'recently_detected_issues' => $recentIssues,
            'recent_activity_logs' => $recentActivityLogs,
            'issue_count_by_severity' => $this->normalizeCounts($severityCounts, ['critical', 'high', 'medium', 'low']),
            'product_count_by_health_status' => $this->normalizeCounts($healthCounts, ['healthy', 'warning', 'critical', 'needs_review']),
            'enabled_validation_rules_count' => ValidationRule::query()->where('is_enabled', true)->count(),
            'disabled_validation_rules_count' => ValidationRule::query()->where('is_enabled', false)->count(),
        ];
    }

    protected function normalizeCounts(array $counts, array $keys): array
    {
        $normalized = [];

        foreach ($keys as $key) {
            $normalized[$key] = (int) ($counts[$key] ?? 0);
        }

        return $normalized;
    }
}
