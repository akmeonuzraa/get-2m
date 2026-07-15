"""01_extraction_texte.py
Extraction de texte depuis PDF/DOCX, nettoyage et export CSV.
Usage: python 01_extraction_texte.py --input_dir PATH_TO_DOCS [--out artifacts/documents_extraits.csv]
"""
import argparse
import os
import re
from pathlib import Path
from collections import defaultdict
import pandas as pd
from tqdm import tqdm

try:
    import pdfplumber
except Exception:
    pdfplumber = None

try:
    import docx
except Exception:
    docx = None

from utils import ARTIFACTS_DIR, save_dataframe


def extract_text_pdf(path: str) -> str:
    if pdfplumber is None:
        raise RuntimeError('pdfplumber not installed (pip install pdfplumber)')
    text_parts = []
    try:
        with pdfplumber.open(path) as pdf:
            for page in pdf.pages:
                text_parts.append(page.extract_text() or '')
    except Exception as e:
        print(f'Error reading PDF {path}: {e}')
    return "\n".join(text_parts)


def extract_text_docx(path: str) -> str:
    if docx is None:
        raise RuntimeError('python-docx not installed (pip install python-docx)')
    text_parts = []
    try:
        d = docx.Document(path)
        for para in d.paragraphs:
            text_parts.append(para.text)
    except Exception as e:
        print(f'Error reading DOCX {path}: {e}')
    return "\n".join(text_parts)


def clean_text(text: str) -> str:
    if not isinstance(text, str):
        return ''
    # Normalize line endings
    t = text.replace('\r', '\n')
    # Replace multiple newlines with double newline
    t = re.sub('\n{3,}', '\n\n', t)
    # Collapse spaces and tabs
    t = re.sub('[ \t]+', ' ', t)
    # Strip leading/trailing whitespace on lines
    lines = [ln.strip() for ln in t.splitlines() if ln.strip()]
    # Heuristic: remove lines that repeat too often (likely headers/footers)
    freq = defaultdict(int)
    for ln in lines:
        freq[ln] += 1
    filtered = [ln for ln in lines if freq.get(ln, 0) <= 3]
    out = '\n'.join(filtered)
    # Ensure UTF-8 safe
    out = out.encode('utf-8', 'replace').decode('utf-8')
    return out.strip()


def process_folder(input_dir: str, out_csv_name: str = 'documents_extraits.csv') -> pd.DataFrame:
    p = Path(input_dir)
    if not p.exists():
        raise FileNotFoundError(input_dir)
    records = []
    file_list = [x for x in p.rglob('*') if x.is_file() and x.suffix.lower() in ['.pdf', '.docx']]
    for f in tqdm(file_list, desc='Processing files'):
        raw = ''
        try:
            if f.suffix.lower() == '.pdf':
                raw = extract_text_pdf(str(f))
            elif f.suffix.lower() == '.docx':
                raw = extract_text_docx(str(f))
        except Exception as e:
            print('Failed to extract', f, e)
            raw = ''
        clean = clean_text(raw)
        nb_mots = len(clean.split())
        records.append({'id': str(f.relative_to(p)), 'texte_brut': raw, 'texte_nettoye': clean, 'nb_mots': nb_mots})
    df = pd.DataFrame(records)
    out_path = save_dataframe(df, out_csv_name)
    print('Saved', out_path)
    return df


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--input_dir', required=True, help='Dossier contenant PDF/DOCX')
    parser.add_argument('--out', default='documents_extraits.csv', help='Nom du CSV de sortie dans ged-ai/artifacts')
    args = parser.parse_args()
    df = process_folder(args.input_dir, out_csv_name=args.out)
    print('Done. Documents:', len(df))
