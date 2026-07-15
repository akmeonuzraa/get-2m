"""Fonction de résumé réutilisable par le service FastAPI (ged_ai_api/).

Résumé extractif par défaut (TextRank, sumy) : rapide, sans GPU, sans appel
réseau. Une fonction de résumé abstractif est disponible en option si un
modèle HuggingFace est pré-téléchargé et disponible localement.
"""
from sumy.parsers.plaintext import PlaintextParser
from sumy.nlp.tokenizers import Tokenizer
from sumy.summarizers.text_rank import TextRankSummarizer


def summarise_extractive(text: str, sentences_count: int = 3) -> str:
    if not text or not text.strip():
        return ""
    parser = PlaintextParser.from_string(text, Tokenizer("french"))
    summarizer = TextRankSummarizer()
    summary = summarizer(parser.document, sentences_count)
    return " ".join(str(s) for s in summary)
