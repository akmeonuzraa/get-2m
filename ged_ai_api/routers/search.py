"""Semantic search router using sentence-transformers embeddings.

Loads artifacts from ../ged-ai/artifacts/:
- embeddings.npy : precomputed embeddings (numpy array)
- embeddings_meta.csv : metadata (id, filename)

Endpoint:
  POST /search { "query": "...", "top_k": 5 } -> { "results": [...] }
"""
import os
import numpy as np
import pandas as pd
from fastapi import APIRouter, HTTPException
from typing import Dict, Any, List
from pathlib import Path

router = APIRouter()

# Construct path to artifacts
PROJECT_ROOT = Path(__file__).parent.parent.parent
ARTIFACTS_DIR = PROJECT_ROOT / "ged-ai" / "artifacts"

# Lazy-load caches
_embeddings = None
_embeddings_meta = None
_model = None


def _load_embeddings():
    global _embeddings, _embeddings_meta
    if _embeddings is None:
        emb_path = ARTIFACTS_DIR / "embeddings.npy"
        meta_path = ARTIFACTS_DIR / "embeddings_meta.csv"
        
        if not emb_path.exists():
            raise FileNotFoundError(f"Embeddings not found at {emb_path}")
        if not meta_path.exists():
            raise FileNotFoundError(f"Metadata not found at {meta_path}")
        
        _embeddings = np.load(str(emb_path))
        _embeddings_meta = pd.read_csv(str(meta_path))
    
    return _embeddings, _embeddings_meta


def _load_model():
    global _model
    if _model is None:
        try:
            from sentence_transformers import SentenceTransformer
            _model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')
        except ImportError:
            raise RuntimeError('sentence-transformers not installed')
    return _model


@router.post("/search")
def search(payload: Dict[str, Any]):
    """
    Semantic search across pre-embedded documents using cosine similarity.

    Request:
      { "query": "procédure sécurité incendie", "top_k": 5 }

    Response:
      {
        "results": [
          { "doc_id": "...", "score": 0.92, "snippet": "..." },
          ...
        ]
      }
    """
    try:
        query = payload.get("query", "")
        top_k = payload.get("top_k", 5)
        
        if not query or not isinstance(query, str):
            raise HTTPException(status_code=400, detail="Missing or invalid 'query' field")
        
        top_k = max(1, min(int(top_k), 100))  # Clamp between 1 and 100
        
        # Load embeddings and model
        embeddings, meta = _load_embeddings()
        model = _load_model()
        
        # Encode query
        query_emb = model.encode([query], convert_to_numpy=True)[0]
        
        # Compute cosine similarity
        dots = embeddings @ query_emb
        norms = np.linalg.norm(embeddings, axis=1) * np.linalg.norm(query_emb)
        sims = dots / (norms + 1e-12)
        
        # Get top-k indices
        top_indices = sims.argsort()[::-1][:top_k]
        
        # Build results
        results = []
        for idx in top_indices:
            doc_id = meta.iloc[idx]['id']
            score = float(sims[idx])
            results.append({
                "doc_id": str(doc_id),
                "score": score,
            })
        
        return {
            "results": results,
            "count": len(results),
        }
    
    except FileNotFoundError as e:
        raise HTTPException(status_code=503, detail=f"Embeddings not found: {str(e)}")
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Search failed: {str(e)}")
