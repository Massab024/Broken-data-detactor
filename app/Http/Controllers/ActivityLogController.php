<?php

namespace App\Http\Controllers;

use App\Models\AppActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()?->id;
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'event' => (string) $request->query('event', 'all'),
            'level' => (string) $request->query('level', 'all'),
            'date' => (string) $request->query('date', ''),
        ];

        $query = AppActivityLog::query()
            ->where('user_id', $userId)
            ->when($filters['search'] !== '', function ($builder) use ($filters) {
                $builder->where(function ($searchQuery) use ($filters) {
                    $term = '%' . $filters['search'] . '%';

                    $searchQuery->where('event', 'like', $term)
                        ->orWhere('message', 'like', $term)
                        ->orWhere('level', 'like', $term)
                        ->orWhere('details', 'like', $term);
                });
            })
            ->when($filters['event'] !== 'all', fn ($builder) => $builder->where('event', $filters['event']))
            ->when($filters['level'] !== 'all', fn ($builder) => $builder->where('level', $filters['level']))
            ->when($filters['date'] !== '', fn ($builder) => $builder->whereDate('created_at', $filters['date']))
            ->orderByDesc('created_at');

        $logs = $query->paginate(10)->withQueryString();
        $logs->getCollection()->transform(fn (AppActivityLog $log) => [
            'id' => $log->id,
            'event' => $log->event,
            'level' => $log->level,
            'message' => $log->message,
            'date' => optional($log->created_at)?->toDateTimeString(),
            'details' => $log->details,
        ]);

        $eventOptions = AppActivityLog::query()
            ->where('user_id', $userId)
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->filter()
            ->map(fn ($event) => [
                'label' => (string) $event,
                'value' => (string) $event,
            ])
            ->values();

        return $this->render('Logs', [
            'logs' => $logs,
            'filters' => $filters,
            'filter_options' => [
                'events' => $eventOptions,
                'levels' => [
                    ['label' => 'All levels', 'value' => 'all'],
                    ['label' => 'Info', 'value' => 'info'],
                    ['label' => 'Warning', 'value' => 'warning'],
                    ['label' => 'Error', 'value' => 'error'],
                ],
            ],
        ]);
    }
}
