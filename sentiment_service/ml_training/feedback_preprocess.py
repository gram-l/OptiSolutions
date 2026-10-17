"""Reproduce the legacy CSV using the shared preprocessing implementation."""
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
from sentiment_api.text_preprocessing import normalize_texts


def clean_text(text):
    return normalize_texts([text], mode='legacy')[0]


if __name__ == '__main__':
    import pandas as pd
    from datasets import load_dataset

    data = load_dataset('ccosme/FiReCS')
    frame = pd.concat([data['train'].to_pandas(), data['test'].to_pandas()], ignore_index=True)
    frame['sentiment'] = frame['label'].map({0.0: 'negative', 1.0: 'neutral', 2.0: 'positive'})
    frame['review_text'] = normalize_texts(frame['review'], mode='legacy')
    output = Path(__file__).resolve().parent / 'data/processed_reviews.csv'
    frame[['review_text', 'label', 'sentiment']].to_csv(output, index=False)
    print(frame['sentiment'].value_counts())
