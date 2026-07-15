<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Client for the local ged-ai-api FastAPI service.
 *
 * Provides non-blocking classification, semantic search, and summarization.
 * All methods return null on failure to allow graceful degradation.
 */
class AiService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.url', 'http://127.0.0.1:8000');
        $this->timeout = 30;
    }

    /**
     * Classify a document text.
     *
     * Returns { "category": "...", "confidence": 0.95, "tags": [...] }
     * or null if service is unavailable.
     *
     * @return array<string, mixed>|null
     */
    public function classify(string $text)
    {
        try {
            $resp = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/classify",
                ['text' => $text]
            );

            if ($resp->ok()) {
                return $resp->json();
            }
        } catch (Throwable $e) {
            // Silently fail and return null to allow document upload to proceed
            \Log::debug('AiService classify failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Semantic search across documents.
     *
     * Returns { "results": [...], "count": N }
     * or null if service is unavailable.
     *
     * @return array<string, mixed>|null
     */
    public function search(string $query, int $topK = 5)
    {
        try {
            $resp = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/search",
                ['query' => $query, 'top_k' => $topK]
            );

            if ($resp->ok()) {
                return $resp->json();
            }
        } catch (Throwable $e) {
            \Log::debug('AiService search failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Summarize a document.
     *
     * Returns { "summary": "...", "original_length": N, "summary_length": N }
     * or null if service is unavailable.
     *
     * @return array<string, mixed>|null
     */
    public function summarize(string $text, int $sentencesCount = 3)
    {
        try {
            $resp = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/summarize",
                ['text' => $text, 'sentences_count' => $sentencesCount]
            );

            if ($resp->ok()) {
                return $resp->json();
            }
        } catch (Throwable $e) {
            \Log::debug('AiService summarize failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if service is healthy.
     */
    public function health(): bool
    {
        try {
            $resp = Http::timeout($this->timeout)->get("{$this->baseUrl}/health");
            return $resp->ok();
        } catch (Throwable $e) {
            return false;
        }
    }
}
