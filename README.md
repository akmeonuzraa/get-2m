# GET-2M — Plateforme Collaborative & GED (SOREAD-2M)

**Une plateforme collaborative et de gestion électronique de documents (GED) pour le Département Systèmes IT et Développement de SOREAD-2M.**

Intégrant l'authentification, la gestion des espaces collaboratifs, le versioning de documents, et des capacités d'intelligence artificielle pour la classification, la recherche sémantique, et la génération de rapports automatiques.

### 🎯 Objectifs principaux

- ✅ Centraliser et versionner les documents pour tous les collaborateurs
- ✅ Gérer les permissions et les rôles (admin, responsable, utilisateur)
- ✅ Fournir une recherche intelligente par embeddings sémantiques
- ✅ Classifier automatiquement les documents (catégories, étiquettes)
- ✅ Générer des rapports d'activité résumés via IA locale
- ✅ Tracer toutes les actions (audit logs) pour la conformité

### 🏗️ Architecture technique

| Composant | Technologie | Rôle |
|-----------|-------------|------|
| **Backend** | Laravel 11 (PHP 8.3) + Eloquent ORM | API REST, métier, authentification, permissions |
| **IA — Exploration** | Jupyter Notebooks + scikit-learn | Classification, embeddings, résumés (source de vérité) |
| **IA — Production** | FastAPI + Ollama | Service de résumé de rapports, LLM local |
| **Base de données** | MySQL / SQLite | Stockage persistant (utilisateurs, documents, espaces) |
| **Cache** | Redis | Optimisation en production |
| **Conteneurisation** | Docker Compose | Orchestration Ollama + FastAPI |

---

## 📊 Fonctionnalités principales

### 👥 Gestion des utilisateurs et permissions
- **Authentification sécurisée** : Laravel Sanctum (tokens API)
- **Contrôle d'accès par rôle (RBAC)** : Admin, Responsable, Utilisateur
- **Audit des actions** : Journalisation complète des modifications et accès sensibles
- **Gestion des espaces collaboratifs** : Espaces partagés avec contrôle d'accès granulaire

### 📄 Gestion des documents
- **Versioning** : Historique complet de chaque document (création, modifications)
- **Organisation hiérarchique** : Dossiers et sous-dossiers
- **Métadonnées enrichies** : Tags, catégories, descriptions, commentaires
- **Support multiformat** : PDF, DOCX, et autres (extraction de texte intégrée)
- **Recherche avancée** : Recherche textuelle et sémantique

### 🤖 Intelligence Artificielle
- **Classification automatique** : TF-IDF + ML (KNN, RandomForest, XGBoost)
- **Embeddings sémantiques** : Recherche intelligente par sens (similarity search)
- **Résumés de documents** : Extraction et résumé automatique du contenu
- **Génération de rapports** : Rapports d'activité générés par LLM local (Ollama)

### 🔔 Notifications et collaboration
- **Système de notifications** : Alertes sur actions (partages, commentaires)
- **Commentaires** : Discussion sur documents
- **Actualités** : Feed d'activités du département

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

## 🧱 Stack technique détaillé

| Catégorie | Technologie | Détails |
|-----------|-------------|---------|
| **Backend API** | Laravel 11, PHP 8.3 | Framework web robuste et sécurisé |
| **ORM & Migrations** | Eloquent, Laravel Migrations | Gestion de la base de données typée |
| **Base de données** | MySQL (production), SQLite (développement) | SGBD relationnel haute performance |
| **Authentification** | Laravel Sanctum | Tokens API sans état, sécurité OAuth 2.0 |
| **Autorisation** | Middleware CheckRole (RBAC) | Contrôle granulaire par rôles |
| **Cache & Sessions** | Redis | Optimisation production + état distribué |
| **Validation JSON** | Middleware ValidateJson | Garantit format avant traitement |
| **Rate Limiting** | Laravel throttle + RateLimit custom | Protection contre abus |
| **Logging & Audit** | LogActivity middleware | Traçabilité conforme |
| **API Uniformité** | ApiEnvelope middleware | Format JSON standardisé |
| **Gestion erreurs** | ApiException + Handler custom | Erreurs structurées pour le frontend |
| **Analyse statique** | PHPStan + Larastan (niveau 5) | Détection d'erreurs sans exécution |
| **Tests** | Pest / PHPUnit | Couverture unitaire et intégration |
| **Frontend** | Vite, Vue/React | Build moderne et hot reload (optionnel) |
| **Module IA** | Python 3.11 | Exploration et prototypage IA |
| **IA — ML** | scikit-learn, XGBoost, sentence-transformers | Classification, embeddings |
| **IA — Résumé** | sumy, spaCy | Extraction et génération de résumés |
| **IA — LLM** | Ollama + Mistral/Llama 3 | Génération de texte locale, sans API |
| **IA API** | FastAPI | Service haute performance pour IA |
| **Conteneurisation** | Docker Compose | Orchestration multi-conteneurs (Ollama, FastAPI) |
| **Analyse statique Python** | pylint / mypy (optionnel) | Qualité du code IA |

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

## ✅ Qualité de code et CI/CD

### Outils de qualité

```bash
# Audit des dépendances PHP
composer audit

# Analyse statique progressive (niveau 5)
vendor/bin/phpstan analyse
# (config progressive : phpstan.neon + phpstan-baseline.neon pour éviter saturation)

# Tests unitaires et intégration
php artisan test
# ou : vendor/bin/pest

# Linting JavaScript/Vite (si frontend)
npm run lint
npm run build
```

### Intégration CI (GitHub Actions) — Recommandations

À mettre en place :
- ✅ **Audit** : `composer audit` — dépendances vulnérables
- ✅ **Static Analysis** : `phpstan analyse` — erreurs sans exécution
- ✅ **Tests** : `php artisan test` — couverture minimale
- ✅ **Database** : Migrations testées sur base clean
- ✅ **API Contract** : Validation format JSON / endpoints

### Baseline et progression

- `phpstan-baseline.neon` : Erreurs connues à corriger progressivement
- Augmentation du niveau de strictité au fil du temps (actuellement niveau 5)

---

## 📖 Documentation et références

- **[ged_ai_api/README.md](./ged_ai_api/README.md)** — Installation d'Ollama, contrat API FastAPI
- **[docs/laravel_report_snippet.md](./docs/laravel_report_snippet.md)** — Exemple d'intégration Laravel → FastAPI
- **Configuration** : `.env.example` — variables essentielles (DB, Redis, Ollama URL)
- **Notebooks IA** : `ged-ai/` — Code source ML, à exécuter avant deployment

---

## 🔄 Workflow de développement

### Branche principale
- `main` : Production-ready, testé et auditée
- `develop` : Branche d'intégration (tests avant merge vers main)

### Avant un commit/PR
1. ✅ Tester localement : `php artisan test`
2. ✅ Analyser : `vendor/bin/phpstan analyse`
3. ✅ Auditer : `composer audit`
4. ✅ Formater si nécessaire (PSR-12 automatique via IDE)

### Pull Request
- Description claire des changements
- Au moins une revue avant merge
- CI doit être verte (tests + analysis + audit)

---

## 🛠️ Dépannage et FAQ

### Laravel n'écoute pas sur http://localhost:8000

```bash
php artisan serve --host=0.0.0.0 --port=8000
# ou vérifier le port 8000 n'est pas utilisé
```

### Migrations ne s'appliquent pas

```bash
php artisan migrate --force  # force sur production
php artisan migrate:rollback  # revenir en arrière si nécessaire
```

### Ollama n'est pas accessible

Vérifier que Docker est lancé et le conteneur tourne :
```bash
docker-compose ps
docker-compose logs ged-ai-api  # logs du service FastAPI
```

### Embeddings obsolètes

Réexécuter les notebooks dans l'ordre (01 → 02 → 03 → 04) pour régénérer les artefacts.

---

## 🚀 Déploiement

### Environnement de production

1. **Variables d'environnement** (`.env`) :
   - `APP_DEBUG=false`
   - `CACHE_DRIVER=redis`
   - `SESSION_DRIVER=redis`
   - Coordonnées de la DB de production

2. **Préparation** :
   ```bash
   composer install --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate --force
   ```

3. **Services** :
   - **Ollama + FastAPI** : Orchestrés via Docker Compose
   - **Laravel** : Serveur applicatif (Apache/Nginx + PHP-FPM)
   - **Redis** : Cache distribué et session store

4. **Monitoring** :
   - Logs d'activité (audit trail)
   - Métriques Laravel (si New Relic / Datadog)
   - Santé des services : health checks

---

## 📧 Support et contribution

- **Département** : Systèmes IT et Développement, SOREAD-2M
- **Questions** : Consultez les docs du projet ou les README spécifiques
- **Bugs** : Ouvrir une issue avec reproduction
- **Contributions** : Fork → Branch → PR avec tests

---

## 📄 Licence et conformité

- Projet interne SOREAD-2M
- Données sensibles : aucune exposition publique (Ollama local)
- Audit trail : traçabilité conforme

---

**Dernière mise à jour** : Juillet 2026