"""04_resume.py
Compare extractive (sumy TextRank) and optional abstractive summarization (mT5).
Exports a summary_tool.py with extractive (and optional abstractive wrapper).

Usage: python 04_resume.py --csv artifacts/documents_extraits.csv
"""
import os
import time
import argparse
import pandas as pd
from utils import ARTIFACTS_DIR

try:
    from sumy.parsers.plaintext import PlaintextParser
    from sumy.nlp.tokenizers import Tokenizer
    from sumy.summarizers.text_rank import TextRankSummarizer
except Exception:
    raise RuntimeError('sumy not installed (pip install sumy)')

abstractive_available = True
try:
    from transformers import pipeline
    # instantiate lazily only if used
except Exception:
    abstractive_available = False


def summarise_extractive(text: str, sentences_count: int = 3) -> str:
    if not text or not text.strip():
        return ''
    parser = PlaintextParser.from_string(text, Tokenizer('french'))
    summarizer = TextRankSummarizer()
    summary = summarizer(parser.document, sentences_count)
    return '\n'.join(str(s) for s in summary)


def summarise_abstractive(text: str, model_name: str = 'csebuetnlp/mT5_multilingual_XLSum', max_length: int = 150) -> str:
    if not abstractive_available:
        raise RuntimeError('transformers not installed')
    # Use pipeline; note: this will download the model if not present and may be heavy
    pipe = pipeline('summarization', model=model_name, device=-1)
    out = pipe(text, max_length=max_length, min_length=40, do_sample=False)
    return out[0]['summary_text']


def compare_methods(csv_path: str, n_samples: int = 10, skip_abstractive: bool = False):
    if not os.path.exists(csv_path):
        raise FileNotFoundError(csv_path)
    df = pd.read_csv(csv_path)
    texts = df['texte_nettoye'].dropna().astype(str).tolist()[:n_samples]
    results = []
    for i, t in enumerate(texts):
        r = {'i': i}
        t0 = time.time(); s_ext = summarise_extractive(t, sentences_count=3); t1 = time.time()
        r['extractive_time'] = t1 - t0; r['extractive'] = s_ext
        # Only run abstractive if available and not explicitly skipped
        if (not skip_abstractive) and abstractive_available:
            t0 = time.time();
            try:
                s_abs = summarise_abstractive(t)
            except Exception as e:
                s_abs = f'Error: {e}'
            t1 = time.time()
            r['abstractive_time'] = t1 - t0; r['abstractive'] = s_abs
        results.append(r)
    out = pd.DataFrame(results)
    out_path = os.path.join(ARTIFACTS_DIR, 'summaries_comparison.csv')
    out.to_csv(out_path, index=False)
    print('Saved comparison to', out_path)
    return out


def export_summary_tool():
    code = """from sumy.parsers.plaintext import PlaintextParser
from sumy.nlp.tokenizers import Tokenizer
from sumy.summarizers.text_rank import TextRankSummarizer


def summarise_extractive(text: str, sentences_count: int = 3) -> str:
    if not text or not text.strip():
        return ''
    parser = PlaintextParser.from_string(text, Tokenizer('french'))
    summarizer = TextRankSummarizer()
    summary = summarizer(parser.document, sentences_count)
    return '\n'.join(str(s) for s in summary)
"""
    path = os.path.join(os.path.dirname(__file__), 'summary_tool.py')
    with open(path, 'w', encoding='utf8') as f:
        f.write(code)
    print('Wrote', path)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--csv', default=os.path.join(ARTIFACTS_DIR, 'documents_extraits.csv'))
    parser.add_argument('--n', type=int, default=10, help='Number of documents to compare')
    parser.add_argument('--no-abstractive', action='store_true', help='Skip abstractive summarization (avoid large model downloads)')
    args = parser.parse_args()
    compare_methods(args.csv, n_samples=args.n, skip_abstractive=args.no_abstractive)
    export_summary_tool()
