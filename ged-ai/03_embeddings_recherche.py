"""03_embeddings_recherche.py
Encode documents with sentence-transformers and save embeddings (.npy + meta csv).
Provides a small CLI search helper.

Usage: python 03_embeddings_recherche.py --csv artifacts/documents_extraits.csv
       python 03_embeddings_recherche.py --query "procédure sécurité incendie"
"""
import argparse
import os
import numpy as np
import pandas as pd
from time import time
from utils import ARTIFACTS_DIR, save_embeddings


def encode_and_save(csv_path: str, model_name: str = 'paraphrase-multilingual-MiniLM-L12-v2'):
    if not os.path.exists(csv_path):
        raise FileNotFoundError(csv_path)
    df = pd.read_csv(csv_path)
    texts = df['texte_nettoye'].fillna('').astype(str).tolist()
    ids = df['id'].tolist() if 'id' in df.columns else list(range(len(texts)))
    try:
        from sentence_transformers import SentenceTransformer
    except Exception:
        raise RuntimeError('Please install sentence-transformers: pip install sentence-transformers')
    model = SentenceTransformer(model_name)
    t0 = time()
    embeddings = model.encode(texts, show_progress_bar=True, convert_to_numpy=True)
    t1 = time()
    print(f'Encoded {len(texts)} documents in {t1-t0:.2f}s. Dim={embeddings.shape[1]}')
    paths = save_embeddings(embeddings, ids, basename='embeddings')
    print('Saved embeddings:', paths)
    return model, embeddings, ids, texts


def load_embeddings(base: str = 'embeddings'):
    import numpy as _np
    npy = os.path.join(ARTIFACTS_DIR, f'{base}.npy')
    meta = os.path.join(ARTIFACTS_DIR, f'{base}_meta.csv')
    emb = _np.load(npy)
    df = pd.read_csv(meta)
    ids = df['id'].tolist()
    return emb, ids


def search_query(model, embeddings, ids, texts, query: str, top_k: int = 5):
    q_emb = model.encode([query], convert_to_numpy=True)[0]
    dots = embeddings @ q_emb
    norms = np.linalg.norm(embeddings, axis=1) * np.linalg.norm(q_emb)
    sims = dots / (norms + 1e-12)
    idx = sims.argsort()[::-1][:top_k]
    return [(ids[i], float(sims[i]), texts[i][:400].replace('\n', ' ')) for i in idx]


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--csv', default=os.path.join(ARTIFACTS_DIR, 'documents_extraits.csv'))
    parser.add_argument('--query', default=None)
    parser.add_argument('--reindex', action='store_true', help='Recompute embeddings from CSV')
    parser.add_argument('--model', default='paraphrase-multilingual-MiniLM-L12-v2')
    args = parser.parse_args()

    if args.reindex:
        model, embeddings, ids, texts = encode_and_save(args.csv, model_name=args.model)
    else:
        # Lazy load model when needed
        try:
            embeddings, ids = load_embeddings('embeddings')
            texts = None
            # Load texts for snippets if available
            try:
                df = pd.read_csv(args.csv)
                texts = df['texte_nettoye'].fillna('').astype(str).tolist()
            except Exception:
                texts = [''] * len(ids)
            from sentence_transformers import SentenceTransformer
            model = SentenceTransformer(args.model)
        except Exception as e:
            print('Embeddings not present or failed to load:', e)
            print('Run with --reindex to compute embeddings from CSV')
            raise SystemExit(1)

    if args.query:
        res = search_query(model, embeddings, ids, texts, args.query, top_k=5)
        print('Top results for query:', args.query)
        for did, score, snippet in res:
            print(f'{did} — {score:.4f} — {snippet[:200]}')
    else:
        print('Run with --query "text" to search, or --reindex to (re)compute embeddings from CSV')
