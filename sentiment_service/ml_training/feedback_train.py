import pandas as pd
import joblib
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.pipeline import FeatureUnion, Pipeline
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import classification_report, accuracy_score

# --- Load preprocessed data ---
df = pd.read_csv("data/processed_reviews.csv")
df = df.dropna(subset=["review_text", "sentiment"])  # safety net for any blank rows after cleaning

X = df["review_text"]
y = df["sentiment"]

# --- Stratified 80/20 split (Phase 4) ---
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)

print(f"Train size: {len(X_train)} | Test size: {len(X_test)}")

# --- Feature extraction: word-level TF-IDF + character n-gram TF-IDF (Phase 3) ---
word_vectorizer = TfidfVectorizer(
    analyzer="word",
    ngram_range=(1, 2),      # unigrams + bigrams for word-level context
    min_df=2,
    max_features=5000
)

char_vectorizer = TfidfVectorizer(
    analyzer="char_wb",      # char n-grams within word boundaries (per your paper's 3-gram example)
    ngram_range=(3, 3),
    min_df=2,
    max_features=5000
)

combined_features = FeatureUnion([
    ("word_tfidf", word_vectorizer),
    ("char_tfidf", char_vectorizer),
])

# --- Full pipeline: features + model (Phase 4) ---
pipeline = Pipeline([
    ("features", combined_features),
    ("clf", LogisticRegression(
        solver="lbfgs",
        max_iter=1000,
        random_state=42
    ))
])

# --- Train ---
pipeline.fit(X_train, y_train)

# --- Evaluate (Phase 5) ---
y_pred = pipeline.predict(X_test)

print("\n=== Accuracy ===")
print(accuracy_score(y_test, y_pred))

print("\n=== Classification Report (per-class + macro/weighted avg) ===")
print(classification_report(y_test, y_pred, digits=4))

# --- Save the full pipeline (vectorizers + model) for reuse in Django ---
joblib.dump(pipeline, "sentiment_model.pkl")
print("\nSaved trained pipeline to sentiment_model.pkl")