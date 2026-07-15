<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController
{
    protected ReportService $reports;

    public function __construct(ReportService $reports)
    {
        $this->reports = $reports;
    }

    /**
     * GET /api/reports/activity?start=2026-07-01&end=2026-07-07
     */
    public function activity(Request $request)
    {
        $this->authorizeRequest($request);

        $start = $request->query('start') ?? now()->startOfWeek()->toDateString();
        $end = $request->query('end') ?? now()->endOfWeek()->toDateString();

        // Normalize ISO date strings
        $startIso = date('c', strtotime($start));
        $endIso = date('c', strtotime($end));

        $report = $this->reports->getActivityReport($startIso, $endIso);

        return response()->json($report);
    }

    protected function authorizeRequest(Request $request): void
    {
        // Basic policy: only authenticated users can access reports
        if (! $request->user()) {
            abort(401, 'Unauthenticated');
        }

        // Additional authorization (example) : role-based could be enforced here
    }
}
