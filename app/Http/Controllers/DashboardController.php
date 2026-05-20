<?php

namespace App\Http\Controllers;

use App\Jobs\ProductSyncJob;
use App\Models\AppActivityLog;
use App\Models\Products\Product;
use App\Models\Products\ProductIssue;
use App\Models\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;




class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user) {

            $check = Product::where('user_id', $user->id)->first();

            if (empty($check)) {
                ProductSyncJob::dispatch($user->id);
            }
        }

        return $this->render('Dashboard', $this->dashboardData());
    }

    public function syncProducts(Request $request)
    {
        ProductSyncJob::dispatch($request->user()->id);

        return back()->with('success', 'Product sync has been queued.');
    }

    protected function dashboardData(): array
    {
        $products = Product::query()->get();

        $healthCounts = Product::query()
            ->select('health_status', DB::raw('COUNT(*) as total'))
            ->groupBy('health_status')
            ->pluck('total', 'health_status')
            ->all();

        $severityCounts = ProductIssue::query()
            ->open()
            ->select('severity', DB::raw('COUNT(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->all();

        $recentIssues = ProductIssue::query()
            ->with(['product:id,title,health_status,last_checked_at'])
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
                'view_url' => route('product.issues', ['issue_id' => $issue->id]),
            ])
            ->values();

        $recentActivityLogs = AppActivityLog::query()
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
            'open_issues_count' => ProductIssue::query()->open()->count(),
            'resolved_issues_count' => ProductIssue::query()->whereNotNull('resolved_at')->count(),
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
