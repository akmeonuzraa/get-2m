"""Document summarization router using extractive summarization (TextRank).

Uses summary_tool.py from ../ged-ai/ which provides summarise_extractive().

Endpoint:
  POST /summarize { "text": "...", "sentences_count": 3 } -> { "summary": "..." }
"""
import sys
from pathlib import Path
from fastapi import APIRouter, HTTPException
from typing import Dict, Any

router = APIRouter()

# Construct path to ged-ai module
PROJECT_ROOT = Path(__file__).parent.parent.parent
GED_AI_DIR = PROJECT_ROOT / "ged-ai"

# Add ged-ai to sys.path so we can import summary_tool
if str(GED_AI_DIR) not in sys.path:
    sys.path.insert(0, str(GED_AI_DIR))

try:
    from summary_tool import summarise_extractive
except ImportError as e:
    raise RuntimeError(f"Failed to import summary_tool from {GED_AI_DIR}: {e}")


@router.post("/summarize")
def summarize(payload: Dict[str, Any]):
    """
    Generate an extractive summary of a document using TextRank (sumy).

    Request:
      { "text": "...", "sentences_count": 3 }

    Response:
      {
        "summary": "Sentence 1. Sentence 2. Sentence 3.",
        "original_length": 5432,
        "summary_length": 1234
      }
    """
    try:
        text = payload.get("text", "")
        sentences_count = payload.get("sentences_count", 3)
        
        if not text or not isinstance(text, str):
            raise HTTPException(status_code=400, detail="Missing or invalid 'text' field")
        
        sentences_count = max(1, int(sentences_count))
        
        # Generate summary
        summary = summarise_extractive(text, sentences_count=sentences_count)
        
        return {
            "summary": summary,
            "original_length": len(text),
            "summary_length": len(summary),
        }
    
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Summarization failed: {str(e)}")
