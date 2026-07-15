<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    /**
     * Generate a human-readable summary using local Ollama-backed service.
     * Caches the result to avoid repeated LLM calls.
     *
     * @param array $metrics Metrics array as returned by getActivityReport()
     * @param int $cacheMinutes
     * @return string
     */
    public function generateSummary(array $metrics, int $cacheMinutes = 60): string
    {
        $start = data_get($metrics, 'period.start', 'period');
        $end = data_get($metrics, 'period.end', 'period');
        $cacheKey = sprintf('reports:summary:%s:%s', $start, $end);

        return Cache::remember($cacheKey, $cacheMinutes * 60, function () use ($metrics) {
            $apiUrl = rtrim(config('services.ai.url'), '/') . '/report-summary';
            try {
                $response = Http::timeout(5)->post($apiUrl, $metrics);
                if (! $response->successful()) {
                    Log::warning('GED AI API responded with status '.$response->status());
                    return $this->buildFallbackSummary($metrics);
                }
                $body = $response->json();
                if (isset($body['hallucination']) && $body['hallucination'] === true) {
                    Log::warning('GED AI summary flagged hallucination, using fallback template.');
                    return $this->buildFallbackSummary($metrics);
                }
                return $body['summary'] ?? $this->buildFallbackSummary($metrics);
            } catch (\Throwable $e) {
                Log::error('Error calling GED AI API: '.$e->getMessage());
                return $this->buildFallbackSummary($metrics);
            }
        });
    }

    /**
     * Builds a safe fallback summary (no LLM) using only supplied metrics.
     */
    protected function buildFallbackSummary(array $metrics): string
    {
        $period = data_get($metrics, 'period', []);
        $start = data_get($period, 'start', '');
        $end = data_get($period, 'end', '');
        $created = data_get($metrics, 'counts.created', 0);

        // get top service and user if present
        $topService = 'N/A';
        $topServiceCnt = 0;
        $byService = data_get($metrics, 'by_service', []);
        if (!empty($byService) && is_array($byService)) {
            foreach ($byService as $svc => $cnt) {
                if ($cnt > $topServiceCnt) {
                    $topService = $svc;
                    $topServiceCnt = $cnt;
                }
            }
        }

        $topUser = 'N/A';
        $topUserCnt = 0;
        $topUsers = data_get($metrics, 'top_users', []);
        if (!empty($topUsers) && is_array($topUsers)) {
            $first = reset($topUsers);
            if (is_array($first)) {
                $topUser = $first['name'] ?? ($first['user_id'] ?? 'N/A');
                $topUserCnt = $first['count'] ?? 0;
            }
        }

        $sentences = [];
        $sentences[] = sprintf('Période %s à %s : %d documents créés.', $start, $end, $created);
        $sentences[] = sprintf('Top service : %s (%d documents).', $topService, $topServiceCnt);
        $sentences[] = sprintf('Top utilisateur : %s (%d actions).', $topUser, $topUserCnt);

        return implode(' ', $sentences);
    }
}
