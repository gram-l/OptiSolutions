"""Pickle-stable preprocessing shared by offline training and Django inference."""
import html
import re
import unicodedata

STOPWORDS = set('ang mga sa ng nang na at ay ako ikaw siya kami tayo kayo sila po opo din rin lang naman kasi pero kung para ito iyon dito diyan doon nito niya namin natin ninyo nila yung ung yun the is and a an to of in it this that for on was with as are be at by or i we'.split())


def normalize_text(text, mode='light'):
    if not isinstance(text, str):
        return ''
    if mode == 'legacy':
        text = re.sub(r'http\S+|www\S+', '', text.lower())
        text = re.sub(r'[^a-zA-ZñÑ\s]', ' ', text)
        return ' '.join(token for token in text.split() if token not in STOPWORDS)
    if mode != 'light':
        raise ValueError(f'Unknown preprocessing mode: {mode}')
    text = unicodedata.normalize('NFKC', html.unescape(text)).lower()
    text = re.sub(r'https?://\S+|www\.\S+', ' ', text)
    # Preserve negations, contrast, numbers and emoji for the vectorizers.
    return ' '.join(text.split())


def normalize_texts(texts, mode='light'):
    return [normalize_text(text, mode=mode) for text in texts]


def model_inputs(model, texts):
    # New pipelines contain preprocessing. Old ones were fitted on cleaned CSVs.
    if 'preprocess' in getattr(model, 'named_steps', {}):
        return list(texts)
    return normalize_texts(texts, mode='legacy')
