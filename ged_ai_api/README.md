GED-AI-API (FastAPI) - résumé automatique des rapports d'activité

But: Ce service suppose qu'Ollama est installé sur le réseau interne et accessible
à l'adresse http://localhost:11434 (ou via un reverse-proxy interne).

Installation rapide (serveur interne):

1. Installer Ollama:
   - Suivre la documentation officielle: https://ollama.com/docs
   - Sur Linux x86_64: télécharger et installer le binaire

2. Télécharger un modèle local (exemples):
   - ollama pull mistral
   - ou ollama pull llama3:8b

3. Lancer le service FastAPI (dans un environnement Python isolé):
   python -m venv venv
   venv\Scripts\activate
   pip install -r requirements.txt
   uvicorn ged_ai_api.main:app --host 127.0.0.1 --port 8000 --workers 1

Sécurité & infra:
 - Ollama doit rester accessible uniquement depuis le réseau interne (firewall, pas d'exposition publique).
 - Les modèles doivent être quantisés si la RAM est limitée (Q4). Mistral 7B fonctionne raisonnablement à partir de 8GB.

Contract d'API (/report-summary):
 - POST /report-summary
 - Body: JSON contenant les métriques calculées par Laravel (period, counts, by_service, top_users, ...)
 - Response: { summary: string, used_template: bool, hallucination: bool, latency: float }

Garde-fou:
 - Le serveur vérifie automatiquement que tous les nombres cités dans le résumé
   existent dans les métriques fournies. En cas d'écart, le service retourne
   un résumé template sûr (sans LLM).

Docker (optionnel) :
 - Un docker-compose est fourni à la racine pour démarrer Ollama et le service FastAPI : docker-compose.yml
 - Ollama est mappé sur 127.0.0.1:11434 (bind localhost uniquement) pour éviter toute exposition publique.
 - Démarrage rapide :
   1) Ouvrir Docker Desktop
   2) docker-compose up --build
   3) Pour charger un modèle dans Ollama depuis le conteneur :
      docker exec -it <ollama_container> ollama pull mistral

Remarques:
 - Si la politique interne interdit l'utilisation d'images publiques, télécharger et installer Ollama manuellement sur le serveur et ne pas utiliser le service ollama du docker-compose.
 - Vérifier les ressources RAM/CPU avant de lancer le modèle (Mistral 7B recommandé à partir de 8GB).

