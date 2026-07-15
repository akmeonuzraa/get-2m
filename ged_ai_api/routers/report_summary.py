# Duplicate of routers/report_summary.py placed under python package ged_ai_api
from fastapi import APIRouter, HTTPException
from typing import Any, Dict
import requests
import json
import re
import time

router = APIRouter()

system_prompt = (
    "Tu es un assistant qui rédige des synthèses d'activité en français. "
    "Utilise uniquement les chiffres fournis, n'invente aucune donnée, "
    "reste factuel en 3-4 phrases maximum."
)


def extract_numbers_from_metrics(metrics: Dict[str, Any]):
    nums = set()

    def add_number(n):
        try:
            if isinstance(n, str):
                if n.endswith('%'):
                    nums.add(float(n.rstrip('%').replace(',', '.')))
                else:
                    nums.add(float(n.replace(',', '.')))
            elif isinstance(n, (int, float)):
                nums.add(float(n))
        except Exception:
            pass

    counts = metrics.get('counts', {}) or {}
    for v in counts.values():
        add_number(v)

    for coll in ('by_service', 'by_file_type'):
        for v in (metrics.get(coll) or {}).values():
            add_number(v)

    for item in metrics.get('top_users', []) or []:
        if isinstance(item, dict):
            add_number(item.get('count') or item.get('cnt') or 0)

    for item in metrics.get('top_documents', []) or []:
        if isinstance(item, dict):
            add_number(item.get('count') or item.get('cnt') or 0)

    # generic numeric keys (e.g. evolution)
    for k, v in (metrics or {}).items():
        if isinstance(v, (int, float)):
            add_number(v)
        if isinstance(v, str) and v.endswith('%'):
            add_number(v)

    return nums


def extract_numbers_from_text(text: str):
    tokens = re.findall(r"\d+(?:[\.|,]\d+)?%?", text)
    out = []
    for t in tokens:
        if t.endswith('%'):
            try:
                out.append(float(t.rstrip('%').replace(',', '.')))
            except Exception:
                pass
        else:
            try:
                out.append(float(t.replace(',', '.')))
            except Exception:
                pass
    return out


@router.post("/report-summary")
def report_summary(payload: Dict[str, Any]):
    """
    Receives metrics JSON and returns a short French summary produced by a local
    Ollama model. Performs a numeric consistency check to avoid hallucination:
    any number present in the generated text must be present in the input metrics.

    Response JSON:
      - summary: string
      - used_template: bool (true when fallback template used)
      - hallucination: bool (true when generated text contained unknown numbers)
      - latency: float (seconds) optional
    """
    # copy payload for safe use
    metrics = payload or {}
    model = metrics.pop('model', None) or 'mistral'

    user_content = "Données: " + json.dumps(metrics, ensure_ascii=False, sort_keys=True)

    body = {
        "model": model,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_content},
        ],
        "max_tokens": 200,
        "temperature": 0.0,
    }

    try:
        start = time.time()
        resp = requests.post("http://localhost:11434/api/generate", json=body, timeout=4)
        latency = time.time() - start
        if resp.status_code != 200:
            raise HTTPException(status_code=502, detail=f"Ollama error: {resp.status_code}")
        data = resp.json()

        # Extract text from common Ollama response shapes
        text = ""
        if isinstance(data, dict):
            if 'text' in data and isinstance(data['text'], str):
                text = data['text']
            elif 'choices' in data and isinstance(data['choices'], list) and len(data['choices']) > 0:
                choice = data['choices'][0]
                text = choice.get('content') or choice.get('text') or ''
            elif 'generation' in data:
                text = data.get('generation') or ''

        if not isinstance(text, str):
            text = str(text)

    except Exception:
        # On any failure, return a safe template built from the metrics
        summary = build_fallback(metrics)
        return {"summary": summary, "used_template": True, "hallucination": False}

    # numeric consistency check
    input_nums = extract_numbers_from_metrics(metrics)
    text_nums = extract_numbers_from_text(text)

    hallucination = False
    for n in text_nums:
        # allow close matches to integers
        matched = False
        for x in input_nums:
            if abs(n - x) < 1e-6 or abs(n - round(x)) < 1e-6:
                matched = True
                break
        if not matched:
            hallucination = True
            break

    if hallucination:
        summary = build_fallback(metrics)
        return {"summary": summary, "used_template": True, "hallucination": True, "latency": latency}

    return {"summary": text.strip(), "used_template": False, "hallucination": False, "latency": latency}


def build_fallback(metrics: Dict[str, Any]) -> str:
    period = metrics.get('period', {}) or {}
    start = period.get('start', '')
    end = period.get('end', '')
    created = (metrics.get('counts', {}) or {}).get('created', 0)

    by_service = metrics.get('by_service', {}) or {}
    top_service = 'N/A'
    top_service_cnt = 0
    if isinstance(by_service, dict) and by_service:
        svc, cnt = max(by_service.items(), key=lambda kv: kv[1])
        top_service = svc
        top_service_cnt = cnt

    top_users = metrics.get('top_users', []) or []
    top_user = 'N/A'
    top_user_cnt = 0
    if isinstance(top_users, list) and len(top_users) > 0 and isinstance(top_users[0], dict):
        first = top_users[0]
        top_user = first.get('name') or str(first.get('user_id', 'N/A'))
        top_user_cnt = first.get('count') or first.get('cnt') or 0

    sentences = []
    sentences.append(f"Période {start} à {end} : {created} documents créés.")
    sentences.append(f"Top service : {top_service} ({top_service_cnt} documents).")
    sentences.append(f"Top utilisateur : {top_user} ({top_user_cnt} actions).")

    return " ".join(sentences)
