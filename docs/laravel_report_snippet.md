Exemples d'utilisation Laravel pour générer et afficher le résumé d'activité

1) Appel depuis un controller (ex: ReportController)

// use App\Services\ReportService; (in top of file)
public function dashboard(Request $request, ReportService $reports)
{
    $start = $request->input('start', now()->startOfMonth()->toIsoString());
    $end = $request->input('end', now()->endOfMonth()->toIsoString());

    // Récupère les metrics (cache interne à getActivityReport)
    $metrics = $reports->getActivityReport($start, $end);

    // Génère (ou récupère en cache) le résumé LLM sécurisé
    $summary = $reports->generateSummary($metrics);

    return view('reports.dashboard', compact('metrics', 'summary'));
}

2) Exemple de structure JSON envoyée au service ged-ai-api (/report-summary)
{
  "period": { "start": "2026-07-01T00:00:00Z", "end": "2026-07-15T23:59:59Z" },
  "counts": { "created": 123, "updated": 45, "deleted": 2 },
  "by_service": { "import": 80, "ocr": 30, "scan": 13 },
  "top_users": [ { "user_id": 7, "name": "Dupont", "count": 50 } ],
  "top_documents": [ { "document_id": 123, "title": "Facture_001.pdf", "count": 20 } ]
}

3) Comportement attendu
- Le résumé retourné contient au maximum 3-4 phrases et n'invente aucun chiffre.
- Si le modèle génère un chiffre non présent dans les metrics, ReportService utilisera un résumé template sûr.

4) Configuration .env
- GED_AI_API_URL (par défaut: http://127.0.0.1:8000) : URL du service ged-ai-api

