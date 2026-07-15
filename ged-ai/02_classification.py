"""02_classification.py
TF-IDF vectorization, comparison of KNN / RandomForest / XGBoost, StratifiedKFold CV,
export of best model and TF-IDF vectorizer to ged-ai/artifacts/.

Usage: python 02_classification.py --csv artifacts/documents_extraits.csv
"""
import argparse
import os
import joblib
import numpy as np
import pandas as pd
from utils import ARTIFACTS_DIR
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.model_selection import StratifiedKFold, cross_val_score
from sklearn.neighbors import KNeighborsClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import make_scorer, f1_score


def main(csv_path: str):
    if not os.path.exists(csv_path):
        raise FileNotFoundError(csv_path)
    df = pd.read_csv(csv_path)
    if 'texte_nettoye' not in df.columns:
        raise RuntimeError('texte_nettoye column missing')
    if 'label' not in df.columns:
        # Create demo labels if missing: user should replace with real labels. Here: label by filename prefix if present
        print('No label column found. Creating demo labels from filename prefixes (before _ or -). Replace with real labels for production.')
        def infer_label(idv):
            s = str(idv)
            s = os.path.basename(s)
            for sep in ['_', '-']:
                if sep in s:
                    return s.split(sep)[0]
            return 'unknown'
        df['label'] = df['id'].apply(infer_label)

    X_texts = df['texte_nettoye'].fillna('').astype(str).tolist()
    y = df['label'].astype(str)

    # TF-IDF
    tfidf = TfidfVectorizer(ngram_range=(1,2), max_features=300)
    X = tfidf.fit_transform(X_texts)

    # classifiers
    clfs = {
        'knn': KNeighborsClassifier(n_neighbors=5),
        'rf': RandomForestClassifier(n_estimators=100, random_state=42)
    }
    try:
        from xgboost import XGBClassifier
        clfs['xgb'] = XGBClassifier(use_label_encoder=False, eval_metric='logloss', random_state=42)
    except Exception:
        print('xgboost not installed — skipped. (pip install xgboost)')

    # Attempt StratifiedKFold where possible, otherwise fall back to a simple fit
    scorer = make_scorer(f1_score, average='weighted')
    results = {}
    try:
        n_splits = min(5, len(y))
        skf = StratifiedKFold(n_splits=n_splits, shuffle=True, random_state=42)
        for name, clf in clfs.items():
            print('Evaluating', name)
            try:
                scores = cross_val_score(clf, X, y, cv=skf, scoring=scorer, n_jobs=-1)
                results[name] = float(np.mean(scores))
                print(f'{name} mean F1-weighted: {results[name]:.4f} (std {np.std(scores):.4f})')
            except Exception as e:
                print('Failed to evaluate', name, e)
    except Exception as e:
        print('StratifiedKFold unavailable for this dataset (too few samples or labels). Will skip CV and fit models directly. Error:', e)

    # If CV produced no result (small dataset), try a simple train/test split evaluation
    if not results:
        print('No CV results. Performing simple train/test split evaluation when possible...')
        try:
            from sklearn.model_selection import train_test_split
            X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42, stratify=y if len(set(y))>1 else None)
            for name, clf in clfs.items():
                try:
                    clf.fit(X_train, y_train)
                    preds = clf.predict(X_test)
                    score = f1_score(y_test, preds, average='weighted')
                    results[name] = float(score)
                    print(f'{name} f1-weighted (holdout): {score:.4f}')
                except Exception as e:
                    print('Failed simple eval for', name, e)
        except Exception as e:
            print('Simple train/test failed or not applicable:', e)

    # If still empty, fit the first classifier and save it (best-effort)
    if not results:
        print('No evaluation possible. Fitting default classifier (RandomForest) on full data and saving (best-effort).')
        default_name = 'rf' if 'rf' in clfs else list(clfs.keys())[0]
        best_name = default_name
        best_clf = clfs[best_name]
        best_clf.fit(X, y)
    else:
        best_name = max(results, key=results.get)
        print('Best model:', best_name, 'score', results[best_name])
        best_clf = clfs[best_name]
        print('Fitting best model on full data...')
        best_clf.fit(X, y)

    model_path = os.path.join(ARTIFACTS_DIR, 'best_classifier.joblib')
    vect_path = os.path.join(ARTIFACTS_DIR, 'tfidf_vectorizer.joblib')
    joblib.dump(best_clf, model_path)
    joblib.dump(tfidf, vect_path)
    print('Saved model to', model_path)
    print('Saved vectorizer to', vect_path)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--csv', default=os.path.join(ARTIFACTS_DIR, 'documents_extraits.csv'))
    args = parser.parse_args()
    main(args.csv)
