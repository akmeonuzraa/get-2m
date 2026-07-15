<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * Generate activity report for a time range.
     * Returns an array of metrics.
     * Cache the result for 10 minutes by default.
     */
    public function getActivityReport(string $startIso, string $endIso, int $cacheMinutes = 10): array
    {
        $start = Carbon::parse($startIso);
        $end = Carbon::parse($endIso);

        $cacheKey = sprintf('reports:activity:%s:%s', $start->toIso8601String(), $end->toIso8601String());

        return Cache::remember($cacheKey, $cacheMinutes * 60, function () use ($start, $end) {
            // Basic counts
            $created = Document::whereBetween('created_at', [$start, $end])->count();
            $updated = Document::whereBetween('updated_at', [$start, $end])->whereColumn('updated_at','>','created_at')->count();

            // Deletions recorded in ActivityLog (fallback if soft deletes used)
            $deleted = ActivityLog::where('action', 'document.delete')
                ->whereBetween('created_at', [$start, $end])->count();

            // Distribution by service and file_type
            $byServiceCollection = Document::selectRaw('service, COUNT(*) as cnt')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('service')
                ->orderByDesc('cnt')
                ->get()->map(function ($r) {
                    return [
                        'service' => $r->service ?? 'unknown',
                        'cnt' => (int) ($r->cnt ?? 0),
                    ];
                });

            $byService = $byServiceCollection->mapWithKeys(fn($a) => [$a['service'] => $a['cnt']]);

            $byFileTypeCollection = Document::selectRaw('file_type, COUNT(*) as cnt')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('file_type')
                ->orderByDesc('cnt')
                ->get()->map(function ($r) {
                    return [
                        'file_type' => $r->file_type ?? 'unknown',
                        'cnt' => (int) ($r->cnt ?? 0),
                    ];
                });

            $byFileType = $byFileTypeCollection->mapWithKeys(fn($a) => [$a['file_type'] => $a['cnt']]);

            // Top users from ActivityLog
            $topUsers = ActivityLog::selectRaw('user_id, COUNT(*) as cnt')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('user_id')
                ->orderByDesc('cnt')
                ->limit(10)
                ->get()->map(function ($r) {
                    $user = User::find($r->user_id);
                    return [
                        'user_id' => $r->user_id,
                        'name' => $user ? $user->name : 'unknown',
                        'count' => (int) ($r->cnt ?? 0),
                    ];
                });

            // Top documents viewed (actions starting with document.view)
            $topDocs = ActivityLog::selectRaw('entity_id, COUNT(*) as cnt')
                ->where('action', 'like', 'document.view%')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('entity_id')
                ->orderByDesc('cnt')
                ->limit(10)
                ->get()->map(function ($r) {
                    $doc = Document::withTrashed()->find($r->entity_id);
                    return [
                        'document_id' => $r->entity_id,
                        'title' => $doc ? $doc->title : 'unknown',
                        'count' => (int) ($r->cnt ?? 0),
                    ];
                });

            // Status distribution
            $statusDistCollection = Document::selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->get()->map(function ($r) {
                    return [
                        'status' => $r->status ?? 'unknown',
                        'cnt' => (int) ($r->cnt ?? 0),
                    ];
                });

            $statusDist = $statusDistCollection->mapWithKeys(fn($a) => [$a['status'] => $a['cnt']]);

            return [
                'period' => [
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                ],
                'counts' => [
                    'created' => $created,
                    'updated' => $updated,
                    'deleted' => $deleted,
                ],
                'by_service' => $byService,
                'by_file_type' => $byFileType,
                'top_users' => $topUsers,
                'top_documents' => $topDocs,
                'status_distribution' => $statusDist,
            ];
        });
    }
}
