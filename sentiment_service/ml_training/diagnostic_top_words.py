import joblib
import numpy as np
import pandas as pd

# Load the trained pipeline
pipeline = joblib.load("sentiment_model.pkl")

features_union = pipeline.named_steps["features"]
clf = pipeline.named_steps["clf"]

# Get feature names in the same order FeatureUnion concatenates them
word_vectorizer = features_union.transformer_list[0][1]  # ("word_tfidf", vectorizer)
char_vectorizer = features_union.transformer_list[1][1]  # ("char_tfidf", vectorizer)

word_features = word_vectorizer.get_feature_names_out()
char_features = char_vectorizer.get_feature_names_out()

all_features = np.concatenate([word_features, char_features])
feature_type = ["word"] * len(word_features) + ["char"] * len(char_features)

# Find which row in coef_ corresponds to the "negative" class
class_index = list(clf.classes_).index("negative")
coefficients = clf.coef_[class_index]

# Build a dataframe, sort by weight descending
df = pd.DataFrame({
    "feature": all_features,
    "type": feature_type,
    "weight": coefficients
}).sort_values("weight", ascending=False)

# Show only word-level features (char n-grams are hard to read/categorize manually)
word_only = df[df["type"] == "word"]

print("=== Top 50 words pushing toward NEGATIVE sentiment ===")
print(word_only.head(50).to_string(index=False))

# Save full list for reference
df.to_csv("negative_class_feature_weights.csv", index=False)
print("\nFull list (word + char features) saved to negative_class_feature_weights.csv")