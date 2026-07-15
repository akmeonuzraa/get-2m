GED-AI notebooks

Contenu:
- 01_extraction_texte.ipynb: extraction PDF/DOCX -> documents_extraits.csv
- 02_classification.ipynb: TF-IDF + KNN / RandomForest / XGBoost, export best model
- 03_embeddings_recherche.ipynb: sentence-transformers embeddings + simple cosine search
- 04_resume.ipynb: extractive (sumy TextRank) and optional abstractive (mT5) summarization

Usage
1. Create a Python 3.11 venv and install:
   pip install pdfplumber python-docx pandas scikit-learn joblib sentence-transformers transformers sumy xgboost tqdm
   (xgboost optional; sklearn's HistGradientBoostingClassifier is used if not installed)
2. Run notebooks in order. Each notebook creates artifacts in ged-ai/artifacts/

Notes
- No external paid API keys used.
- Artifacts are written to ged-ai/artifacts for reuse by the FastAPI service ged_ai_api/.
