import re
import pandas as pd
from datasets import load_dataset

# --- Load FiReCS (reuses HF cache from inspect_dataset.py) ---
ds = load_dataset("ccosme/FiReCS")

train_df = ds["train"].to_pandas()
test_df = ds["test"].to_pandas()

# Combine now — Phase 4 will do its own 80/20 split later
df = pd.concat([train_df, test_df], ignore_index=True)

# --- Label mapping (verify against your samples) ---
label_map = {0.0: "negative", 1.0: "neutral", 2.0: "positive"}
df["sentiment"] = df["label"].map(label_map)

# --- Tagalog + English stopwords ---
TAGALOG_STOPWORDS = {
    "ang", "mga", "sa", "ng", "nang", "na", "at", "ay", "ako", "ikaw", "siya",
    "kami", "tayo", "kayo", "sila", "po", "opo", "din", "rin", "lang", "naman",
    "kasi", "pero", "kung", "para", "ito", "iyon", "dito", "diyan", "doon",
    "nito", "niya", "namin", "natin", "ninyo", "nila", "yung", "ung", "yun"
}

ENGLISH_STOPWORDS = {
    "the", "is", "and", "a", "an", "to", "of", "in", "it", "this", "that",
    "for", "on", "was", "with", "as", "are", "be", "at", "by", "or", "i", "we"
}

STOPWORDS = TAGALOG_STOPWORDS | ENGLISH_STOPWORDS

def clean_text(text: str) -> str:
    text = str(text).lower()
    text = re.sub(r"http\S+|www\S+", "", text)          # URLs
    text = re.sub(r"[^a-zA-ZñÑ\s]", " ", text)           # special chars/numbers
    text = re.sub(r"\s+", " ", text).strip()
    tokens = text.split()
    tokens = [t for t in tokens if t not in STOPWORDS]
    return " ".join(tokens)

df["review_text"] = df["review"].apply(clean_text)

# --- Save for Phase 3 ---
out = df[["review_text", "label", "sentiment"]]
out.to_csv("data/processed_reviews.csv", index=False)   # relative to ml_training/

print(out["sentiment"].value_counts())
print(out.head())