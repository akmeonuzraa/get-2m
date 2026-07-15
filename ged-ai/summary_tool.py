from sumy.parsers.plaintext import PlaintextParser
from sumy.nlp.tokenizers import Tokenizer
from sumy.summarizers.text_rank import TextRankSummarizer


def summarise_extractive(text: str, sentences_count: int = 3) -> str:
    if not text or not text.strip():
        return ''
    parser = PlaintextParser.from_string(text, Tokenizer('french'))
    summarizer = TextRankSummarizer()
    summary = summarizer(parser.document, sentences_count)
    return '\n'.join(str(s) for s in summary)
