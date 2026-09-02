import pandas as pd
import joblib
import os
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.pipeline import Pipeline
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import classification_report, accuracy_score

# --- Load seed data ---
df = pd.read_csv("data/seed_issue_data.csv")

CATEGORIES = [
    "waiting_time",
    "staff_attitude",
    "consultation_fees",
    "medical_certificate",
    "doctor_expertise",
]

os.makedirs("issue_models", exist_ok=True)

print(f"Total seed examples: {len(df)}\n")

for category in CATEGORIES:
    print(f"{'=' * 60}")
    print(f"Training binomial LR for: {category}")
    print(f"{'=' * 60}")

    X = df["text"]
    y = df[category]

    print(f"Positive examples: {y.sum()} / {len(y)}")

    # Small dataset — stratified split still works since each category
    # has both 0s and 1s, but keep test size modest
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(
            analyzer="word",
            ngram_range=(1, 2),
            min_df=1,
            max_features=1000
        )),
        ("clf", LogisticRegression(
            solver="lbfgs",
            max_iter=1000,
            random_state=42,
            class_weight="balanced"  # seed data is imbalanced (few positives per category)
        ))
    ])

    pipeline.fit(X_train, y_train)

    y_pred = pipeline.predict(X_test)
    print(f"\nAccuracy: {accuracy_score(y_test, y_pred):.4f}")
    print(classification_report(y_test, y_pred, digits=4, zero_division=0))

    # --- Layer 4: rank coefficients ---
    vectorizer = pipeline.named_steps["tfidf"]
    clf = pipeline.named_steps["clf"]
    feature_names = vectorizer.get_feature_names_out()
    coefficients = clf.coef_[0]

    coef_df = pd.DataFrame({
        "feature": feature_names,
        "weight": coefficients
    }).sort_values("weight", ascending=False)

    print(f"\nTop 10 predictive words for '{category}':")
    print(coef_df.head(10).to_string(index=False))

    # Save model + coefficient ranking
    joblib.dump(pipeline, f"issue_models/{category}_model.pkl")
    coef_df.to_csv(f"issue_models/{category}_coefficients.csv", index=False)
    print(f"\nSaved issue_models/{category}_model.pkl\n")

print("All category models trained and saved.")