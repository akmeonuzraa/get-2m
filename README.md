# GET-2M — Plateforme Collaborative & GED (SOREAD-2M)

Plateforme collaborative interne intégrant une Gestion Électronique de Documents
(GED), développée pour le Département Systèmes IT et Développement de SOREAD-2M.

Architecture hybride :
- **Laravel (PHP)** — backend métier : authentification, CRUD, permissions, API REST
- **Python (notebooks → FastAPI)** — module IA : classification, recherche
  sémantique, résumé de documents ; LLM local (Ollama) pour les rapports d'activité

---

## 📁 Structure du dépôt

```
get-2m/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # AuthController, DocumentController, ReportController...
│   │   └── Middleware/         # ApiEnvelope, CheckRole, Cors, LogActivity,
│   │                           # RateLimit, RequestTiming, ValidateJson
│   ├── Models/                 # User, Document, DocumentVersion, Folder, Space,
│   │                           # SpaceMember, ActivityLog
│   ├── Services/
│   └── Exceptions/
├── database/
│   ├── migrations/             # users, spaces, documents, folders, comments,
│   │                           # activity_logs, news, notifications...
│   ├── seeders/
│   └── factories/
├── routes/
│   └── api.php                 # /login, /me, /documents, /reports/activity...
├── ged-ai/                     # Notebooks Python (source de vérité du module IA)
│   ├── 01_extraction_texte.ipynb
│   ├── 02_classification.ipynb
│   ├── 03_embeddings_recherche.ipynb
│   ├── 04_resume.ipynb
│   ├── utils.py
│   ├── summary_tool.py
│   └── artifacts/               # généré par les notebooks (csv, joblib, embeddings)
├── ged_ai_api/                  # Service FastAPI — résumé de rapports via Ollama
│   ├── main.py
│   ├── routers/report_summary.py
│   ├── Dockerfile
│   └── requirements.txt
├── docs/
│   └── laravel_report_snippet.md
├── docker-compose.yml           # Ollama + ged-ai-api
├── phpstan.neon / phpstan-baseline.neon
├── composer.json / package.json
└── .env.example
```

---

## 🧱 Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| ORM | Eloquent |
| Base de données | MySQL (SQLite en local par défaut, voir `.env.example`) |
| Authentification | Laravel Sanctum |
| Autorisation (RBAC) | Middleware `CheckRole` (rôles : `admin`, `responsable`, `user`) |
| Cache / Sessions | Redis (recommandé en production) |
| Analyse statique | PHPStan / Larastan (niveau 5) |
| Module IA — exploration | Notebooks Jupyter (`ged-ai/`) : scikit-learn, sentence-transformers, sumy, XGBoost |
| Module IA — production | Service FastAPI (`ged_ai_api/`) |
| Résumé de rapports | LLM local via Ollama (Mistral 7B / Llama 3 8B) — aucune API payante |

---

## 🔐 Sécurité (middlewares Laravel)

Chaîne de middlewares appliquée aux routes de l'API (`routes/api.php`) :

- **`Cors`** — restreint les origines autorisées
- **`RequestTiming`** — logue chaque requête (méthode, durée, statut)
- **`throttle:*`** — rate limiting natif Laravel (ex. `throttle:10,1` sur `/login`)
- **`RateLimit`** — classe complémentaire, disponible pour un contrôle plus fin (Redis recommandé pour l'atomicité en environnement multi-instances)
- **`auth:sanctum`** — authentification par token
- **`CheckRole`** — contrôle d'accès par rôle (`role:admin,responsable,user`)
- **`ValidateJson`** — valide que le body est du JSON correctement formé avant la validation métier
- **`LogActivity`** — journalise les actions sensibles (ex. `log.activity:document.delete`)
- **`ApiEnvelope`** — uniformise toutes les réponses JSON (succès et erreurs) selon `{ success, data|error }`
- **`ApiException` / Handler** — centralise la gestion des exceptions custom

---

## 🤖 Module IA — vue d'ensemble

Deux volets distincts, développés séparément :

### 1. Classification, recherche sémantique, résumé de documents (`ged-ai/`)

Développé et validé en notebooks Jupyter avant tout export vers un service de
production — **les notebooks sont la source de vérité**, pas le service.

| Notebook | Rôle | Artefacts produits |
|---|---|---|
| `01_extraction_texte.ipynb` | Extraction PDF/DOCX + nettoyage | `documents_extraits.csv` |
| `02_classification.ipynb` | TF-IDF + KNN/RandomForest/XGBoost | `best_classifier.joblib`, `tfidf_vectorizer.joblib`, `label_encoder.joblib` |
| `03_embeddings_recherche.ipynb` | Embeddings sémantiques + recherche cosinus | `embeddings.npy`, `embeddings_meta.csv` |
| `04_resume.ipynb` | Résumé extractif (sumy) + option abstractive | `summary_tool.py` |

Aucune API payante n'est utilisée : `sentence-transformers` et les modèles
HuggingFace tournent en local. Ces notebooks incluent un mode de repli
automatique (TF-IDF+SVD) si le modèle ne peut pas être téléchargé — en
production, le modèle doit être pré-téléchargé et mis en cache dans l'image
Docker plutôt que de dépendre du fallback. Voir `ged-ai/` pour le détail.

### 2. Résumé automatique des rapports d'activité (`ged_ai_api/`)

Service FastAPI séparé, appelé par Laravel, qui génère un résumé en langage
naturel des métriques d'activité (documents créés, top catégories, top
utilisateurs) via un LLM local **Ollama** — aucune donnée ne sort du réseau
interne 2M.

- Endpoint : `POST /report-summary`
- Garde-fou anti-hallucination : les chiffres cités dans le résumé sont
  vérifiés automatiquement contre les métriques fournies ; en cas d'écart, un
  résumé template (sans LLM) est retourné à la place
- Voir `ged_ai_api/README.md` pour l'installation détaillée d'Ollama et le
  contrat d'API complet

---

## 🚀 Démarrage rapide

### Backend Laravel

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Module IA — notebooks

```bash
cd ged-ai
pip install -r requirements.txt   # ou installer manuellement les dépendances listées dans le notebook 01
jupyter notebook
# exécuter les notebooks dans l'ordre 01 → 02 → 03 → 04
```

### Service FastAPI + Ollama (résumé de rapports)

```bash
docker-compose up --build
```

Ollama et le service FastAPI sont exposés uniquement sur `127.0.0.1` (aucune
exposition publique). Voir `ged_ai_api/README.md` si Docker n'est pas
autorisé sur l'infrastructure interne (installation manuelle d'Ollama).

---

## ✅ Qualité de code

```bash
composer audit              # audit des dépendances PHP
vendor/bin/phpstan analyse  # analyse statique (config progressive : phpstan.neon + baseline)
php artisan test             # tests Pest/PHPUnit
```

À intégrer en CI (GitHub Actions) : `composer audit` + `phpstan` + tests à
chaque pull request.