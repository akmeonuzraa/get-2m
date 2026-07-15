"""Document classification router using TF-IDF + pre-trained classifier.

Loads artifacts from ../ged-ai/artifacts/:
- best_classifier.joblib : trained scikit-learn classifier
- tfidf_vectorizer.joblib : TF-IDF vectorizer
- label_encoder.joblib : label encoder for class names

Endpoint:
  POST /classify { "text": "..." } -> { "category": "...", "confidence": 0.95, "tags": [...] }
"""
import os
import joblib
from fastapi import APIRouter, HTTPException
from typing import Dict, Any, List
from pathlib import Path

router = APIRouter()

# Construct path to artifacts (up from ged_ai_api/ to project root, then ged-ai/artifacts/)
PROJECT_ROOT = Path(__file__).parent.parent.parent
ARTIFACTS_DIR = PROJECT_ROOT / "ged-ai" / "artifacts"

# Lazy-load caches
_classifier = None
_vectorizer = None
_label_encoder = None


def _load_classifier():
    global _classifier
    if _classifier is None:
        clf_path = ARTIFACTS_DIR / "best_classifier.joblib"
        if not clf_path.exists():
            raise FileNotFoundError(f"Classifier not found at {clf_path}")
        _classifier = joblib.load(str(clf_path))
    return _classifier


def _load_vectorizer():
    global _vectorizer
    if _vectorizer is None:
        vect_path = ARTIFACTS_DIR / "tfidf_vectorizer.joblib"
        if not vect_path.exists():
            raise FileNotFoundError(f"Vectorizer not found at {vect_path}")
        _vectorizer = joblib.load(str(vect_path))
    return _vectorizer


def _load_label_encoder():
    global _label_encoder
    if _label_encoder is None:
        enc_path = ARTIFACTS_DIR / "label_encoder.joblib"
        if not enc_path.exists():
            raise FileNotFoundError(f"Label encoder not found at {enc_path}")
        _label_encoder = joblib.load(str(enc_path))
    return _label_encoder


@router.post("/classify")
def classify(payload: Dict[str, Any]):
    """
    Classify a document text using pre-trained TF-IDF + classifier.

    Request:
      { "text": "..." }

    Response:
      {
        "category": "Procedures_internes",
        "confidence": 0.92,
        "tags": ["procedure", "internal"]
      }
    """
    try:
        text = payload.get("text", "")
        if not text or not isinstance(text, str):
            raise HTTPException(status_code=400, detail="Missing or invalid 'text' field")

        # Load models
        clf = _load_classifier()
        vec = _load_vectorizer()
        enc = _load_label_encoder()

        # Vectorize
        X = vec.transform([text])

        # Predict
        pred_label = clf.predict(X)[0]
        pred_proba = clf.predict_proba(X)[0]
        confidence = float(max(pred_proba))

        # Decode label
        try:
            category = enc.inverse_transform([pred_label])[0]
        except Exception:
            category = str(pred_label)

        # Simple tag extraction from text
        text_lower = text.lower()
        tags = []
        keywords = [
            "procédure", "contrat", "rh", "technique", "sécurité", "incendie",
            "formation", "risque", "audit", "rapport", "manuel", "guide"
        ]
        for kw in keywords:
            if kw in text_lower:
                tags.append(kw)

        return {
            "category": category,
            "confidence": confidence,
            "tags": list(set(tags))[:5],  # Max 5 unique tags
        }

    except FileNotFoundError as e:
        raise HTTPException(status_code=503, detail=f"Classifier artifacts not found: {str(e)}")
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Classification failed: {str(e)}")
