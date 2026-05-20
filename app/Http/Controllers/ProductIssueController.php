<?php

namespace App\Http\Controllers;

use App\Models\Products\ProductIssue;
use App\Models\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductIssueController extends Controller
{
    public function index(Request $request)
    {
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
                'detected_at' => optional($issue->created_at)?->toDateTimeString(),
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
                    'detected_at' => optional($issue->created_at)?->toDateTimeString(),
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
}
