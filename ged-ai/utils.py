"""Utilities shared by the GED-AI notebooks.
"""
from typing import List, Dict, Any
import os
import pandas as pd
import numpy as np

ARTIFACTS_DIR = os.path.join(os.path.dirname(__file__), 'artifacts')
if not os.path.exists(ARTIFACTS_DIR):
    os.makedirs(ARTIFACTS_DIR, exist_ok=True)


def save_dataframe(df: pd.DataFrame, name: str) -> str:
    path = os.path.join(ARTIFACTS_DIR, name)
    df.to_csv(path, index=False)
    return path


def load_documents_csv(name: str = 'documents_extraits.csv') -> pd.DataFrame:
    path = os.path.join(ARTIFACTS_DIR, name)
    return pd.read_csv(path)


def save_embeddings(embeddings: np.ndarray, ids: List[Any], basename: str = 'embeddings') -> Dict[str,str]:
    """Save embeddings as .npy and metadata as csv for easy reload.
    Returns dict with paths.
    """
    npy_path = os.path.join(ARTIFACTS_DIR, f"{basename}.npy")
    meta_path = os.path.join(ARTIFACTS_DIR, f"{basename}_meta.csv")
    np.save(npy_path, embeddings)
    pd.DataFrame({'id': ids}).to_csv(meta_path, index=False)
    return {'npy': npy_path, 'meta': meta_path}


def load_embeddings(basename: str = 'embeddings'):
    import numpy as _np
    npy_path = os.path.join(ARTIFACTS_DIR, f"{basename}.npy")
    meta_path = os.path.join(ARTIFACTS_DIR, f"{basename}_meta.csv")
    emb = _np.load(npy_path)
    meta = pd.read_csv(meta_path)
    return emb, meta
