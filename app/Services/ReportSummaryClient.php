<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ReportSummaryClient
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.report_summary.endpoint', 'http://localhost:11434/api/generate');
    }

    /**
     * Send metrics to the local LLM service and return generated summary text.
     * This is a lightweight client; the LLM server must respect the prompt contract.
     */
    public function generate(array $metrics): ?string
    {
        try {
            $resp = Http::timeout(10)->post($this->endpoint, [
                'metrics' => $metrics,
            ]);

            if ($resp->ok()) {
                $data = $resp->json();
                return $data['text'] ?? $data['result'] ?? null;
            }
        } catch (\Throwable $e) {
            // Swallow and return null so caller can fallback to template
        }

        return null;
    }
}
