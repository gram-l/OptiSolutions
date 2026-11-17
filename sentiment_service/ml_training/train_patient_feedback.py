"""Compare a clinic-trained sentiment candidate with the active model."""

import argparse
import hashlib
import json
from pathlib import Path

from feedback_train import HERE, LABELS, pipeline, report
import joblib
import pandas as pd
from sklearn.model_selection import GridSearchCV, StratifiedKFold, train_test_split
from threadpoolctl import threadpool_limits

from sentiment_api.text_preprocessing import model_inputs, normalize_texts


def prepare(path):
    rows = pd.read_csv(path, dtype=str, keep_default_na=False)
    if not {'review_text', 'sentiment'} <= set(rows.columns):
        raise ValueError('CSV must contain review_text and sentiment columns.')

    frame = rows[['review_text', 'sentiment']].copy()
    frame['sentiment'] = frame['sentiment'].str.strip().str.lower()
    if not frame['sentiment'].isin(LABELS).all():
        raise ValueError('Every sentiment must be negative, neutral, or positive.')

    frame['normalized'] = normalize_texts(frame['review_text'])
    nonempty = frame.loc[frame['normalized'].ne('')].copy()
    conflicts = nonempty.groupby('normalized')['sentiment'].transform('nunique').gt(1)
    unique = nonempty.loc[~conflicts].drop_duplicates('normalized').copy()

    # Keep text from the original training corpus out of the clinic benchmark.
    original = pd.read_csv(HERE / 'data' / 'processed_reviews.csv', usecols=['review_text'])
    original_text = set(original['review_text'].dropna()) - {''}
    overlap = pd.Series(normalize_texts(unique['review_text'], mode='legacy'), index=unique.index).isin(original_text)
    clinic = unique.loc[~overlap]
    counts = clinic['sentiment'].value_counts().reindex(LABELS, fill_value=0)
    if counts.min() < 10:
        raise ValueError('At least 10 usable examples per sentiment are needed for the split and training folds.')

    audit = {
        'input_rows': len(rows),
        'empty_rows': len(frame) - len(nonempty),
        'conflicting_label_rows_excluded': int(conflicts.sum()),
        'same_label_duplicates_excluded': len(nonempty) - int(conflicts.sum()) - len(unique),
        'original_corpus_overlaps_excluded': int(overlap.sum()),
        'usable_rows_by_sentiment': counts.to_dict(),
    }
    return clinic, audit


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--data', type=Path, required=True, help='De-identified, human-labeled clinic CSV')
    parser.add_argument('--output-dir', type=Path, required=True, help='New directory for the candidate and metrics')
    args = parser.parse_args()

    clinic, audit = prepare(args.data)
    train, test = train_test_split(clinic, test_size=0.2, random_state=42, stratify=clinic['sentiment'])
    active_path = HERE / 'sentiment_model.pkl'
    active = joblib.load(active_path)
    args.output_dir.mkdir(parents=True, exist_ok=False)

    search = GridSearchCV(
        pipeline(mode='light', rich=True),
        {'clf__C': [0.5, 1.0, 4.0], 'clf__class_weight': [None, 'balanced']},
        scoring='f1_macro',
        cv=StratifiedKFold(n_splits=5, shuffle=True, random_state=42),
        error_score='raise',
    )
    with threadpool_limits(limits=1):
        search.fit(train['review_text'], train['sentiment'])
        candidate_path = args.output_dir / 'sentiment_model_candidate.pkl'
        joblib.dump(search.best_estimator_, candidate_path)
        candidate = joblib.load(candidate_path)
        active_metrics, _ = report(active, model_inputs(active, test['review_text']), test['sentiment'])
        candidate_metrics, _ = report(candidate, model_inputs(candidate, test['review_text']), test['sentiment'])

    result = {
        'audit': audit,
        'dataset_sha256': hashlib.sha256(args.data.read_bytes()).hexdigest(),
        'active_model_sha256': hashlib.sha256(active_path.read_bytes()).hexdigest(),
        'training_rows': len(train),
        'holdout_rows': len(test),
        'holdout_class_order': LABELS,
        'best_parameters': search.best_params_,
        'training_cv_macro_f1': search.best_score_,
        'active_model': active_metrics,
        'clinic_candidate': candidate_metrics,
    }
    (args.output_dir / 'evaluation.json').write_text(json.dumps(result, indent=2) + '\n', encoding='utf-8')
    for name, scores in [('Active model', active_metrics), ('Clinic candidate', candidate_metrics)]:
        neutral = scores['classification_report']['neutral']
        print(f"{name}: accuracy {scores['accuracy']:.2%}, macro F1 {scores['macro_f1']:.2%}, "
              f"neutral precision {neutral['precision']:.2%}, recall {neutral['recall']:.2%}, F1 {neutral['f1-score']:.2%}")


if __name__ == '__main__':
    main()
