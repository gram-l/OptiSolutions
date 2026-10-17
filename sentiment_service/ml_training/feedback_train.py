"""Audited LR tuning. Does not overwrite the app's sentiment_model.pkl.

Run from any directory: python feedback_train.py --folds 5 --jobs 1
Cached FiReCS CSVs are used; pass --data-dir for a different local snapshot.
"""
import os
os.environ.setdefault('OMP_NUM_THREADS', '1')
os.environ.setdefault('OPENBLAS_NUM_THREADS', '1')
os.environ.setdefault('MKL_NUM_THREADS', '1')
import argparse
import hashlib
import json
import platform
import sys
import time
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
import joblib
import numpy as np
import pandas as pd
import sklearn
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix, f1_score
from sklearn.model_selection import GridSearchCV, StratifiedKFold, train_test_split
from sklearn.pipeline import FeatureUnion, Pipeline
from sklearn.preprocessing import FunctionTransformer
from threadpoolctl import threadpool_limits
from sentiment_api.text_preprocessing import normalize_texts

LABELS = ['negative', 'neutral', 'positive']
HERE = Path(__file__).resolve().parent


def pipeline(mode='legacy', rich=False):
    return Pipeline([
        ('preprocess', FunctionTransformer(normalize_texts, kw_args={'mode': mode})),
        ('features', FeatureUnion([
            ('word_tfidf', TfidfVectorizer(ngram_range=(1, 2), min_df=2,
                max_features=20000 if rich else 5000, sublinear_tf=rich)),
            ('char_tfidf', TfidfVectorizer(analyzer='char_wb', ngram_range=(3, 5) if rich else (3, 3),
                min_df=2, max_features=30000 if rich else 5000, sublinear_tf=rich)),
        ])),
        ('clf', LogisticRegression(solver='lbfgs', max_iter=2000, random_state=42)),
    ])


def report(model, text, labels):
    predicted = model.predict(text)
    return {'accuracy': accuracy_score(labels, predicted),
        'macro_f1': f1_score(labels, predicted, average='macro'),
        'classification_report': classification_report(labels, predicted, labels=LABELS, output_dict=True, zero_division=0),
        'confusion_matrix': confusion_matrix(labels, predicted, labels=LABELS).tolist()}, predicted


def data_directory(explicit):
    if explicit:
        return Path(explicit).resolve()
    cache = Path.home() / '.cache/huggingface/hub/datasets--ccosme--FiReCS/snapshots'
    snapshots = sorted(path for path in cache.iterdir() if (path / 'FiReCS_train_set.csv').exists())
    if not snapshots:
        raise FileNotFoundError('No cached FiReCS CSVs. Download the dataset or specify --data-dir.')
    return snapshots[-1]


def prepare(directory):
    parts = []
    for split in ['train', 'test']:
        part = pd.read_csv(directory / f'FiReCS_{split}_set.csv')
        part['source_split'] = split
        parts.append(part)
    frame = pd.concat(parts, ignore_index=True)
    frame['row_id'] = np.arange(len(frame))
    frame['sentiment'] = frame['label'].map({0.0: 'negative', 1.0: 'neutral', 2.0: 'positive'})
    frame['legacy_text'] = normalize_texts(frame['review'], mode='legacy')
    # Prove raw rows align with the exact CSV used for the published split.
    original = pd.read_csv(HERE / 'data/processed_reviews.csv').fillna('')
    if len(original) != len(frame) or original['review_text'].tolist() != frame['legacy_text'].tolist() or original['sentiment'].tolist() != frame['sentiment'].tolist():
        raise ValueError('Raw data does not reproduce the legacy CSV; refusing to invent a comparable split.')
    valid = frame[frame['sentiment'].notna() & frame['legacy_text'].ne('')].copy()
    train_ids, test_ids = train_test_split(valid.index, test_size=0.2, random_state=42, stratify=valid['sentiment'])
    legacy_train, legacy_test = valid.loc[train_ids].copy(), valid.loc[test_ids].copy()
    # Use the legacy representation as a conservative duplicate group. Variants
    # differing only in removed stopwords must never cross training/test folds.
    conflicts = set(valid.groupby('legacy_text')['sentiment'].nunique().loc[lambda counts: counts > 1].index)
    clean_test = legacy_test[~legacy_test['legacy_text'].isin(conflicts)].drop_duplicates('legacy_text').copy()
    test_groups = set(legacy_test['legacy_text'])
    clean_train = legacy_train[~legacy_train['legacy_text'].isin(conflicts | test_groups)].drop_duplicates('legacy_text').copy()
    audit = {'raw_rows': len(frame), 'usable_rows': len(valid),
        'class_counts': valid['sentiment'].value_counts().to_dict(),
        'exact_duplicate_rows': int(frame.duplicated('review').sum()),
        'normalized_duplicate_rows': int(valid.duplicated('legacy_text').sum()),
        'conflicting_normalized_groups': len(conflicts),
        'legacy_train_rows': len(legacy_train), 'legacy_test_rows': len(legacy_test),
        'legacy_train_test_duplicate_groups': len(set(legacy_train['legacy_text']) & test_groups),
        'clean_train_rows': len(clean_train), 'clean_test_rows': len(clean_test),
        'clean_train_class_counts': clean_train['sentiment'].value_counts().to_dict(),
        'clean_test_class_counts': clean_test['sentiment'].value_counts().to_dict(),
        'train_test_group_overlap_after_cleaning': len(set(clean_train['legacy_text']) & set(clean_test['legacy_text'])),
        'data_snapshot': directory.name,
        'evaluation_note': 'Legacy random-state-42 holdout was previously reported, not a fresh independent test. Excluded from all tuning. Cleaned holdout removes duplicate/conflicting groups; scores on it are not directly interchangeable with the old table.',
        'domain_note': 'FiReCS is primarily Taglish product reviews, not a labeled clinic feedback benchmark.'}
    return frame, legacy_train, legacy_test, clean_train, clean_test, audit


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--data-dir')
    parser.add_argument('--output-dir', type=Path)
    parser.add_argument('--folds', type=int, default=5)
    parser.add_argument('--jobs', type=int, default=1)
    args = parser.parse_args()
    output = args.output_dir or HERE / 'experiments' / datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')
    output.mkdir(parents=True, exist_ok=False)
    start = time.monotonic()
    frame, old_train, old_test, train, test, audit = prepare(data_directory(args.data_dir))
    (output / 'audit.json').write_text(json.dumps(audit, indent=2), encoding='utf-8')
    print(json.dumps(audit, indent=2), flush=True)
    assignments = frame[['row_id', 'source_split', 'sentiment']].copy()
    assignments['partition'] = 'excluded'
    assignments.loc[train.index, 'partition'] = 'train_cv'
    assignments.loc[test.index, 'partition'] = 'test'
    assignments.to_csv(output / 'split_manifest.csv', index=False)
    # Select only by macro F1 on training folds. No test prediction occurs here.
    options = []
    for mode, rich in [('legacy', False), ('light', False), ('light', True)]:
        candidate = pipeline(mode, rich)
        options.append({'preprocess__kw_args': [{'mode': mode}],
            'features': [candidate.named_steps['features']],
            'clf__C': [0.5, 1.0, 4.0, 16.0], 'clf__class_weight': [None, 'balanced']})
    folds = StratifiedKFold(n_splits=args.folds, shuffle=True, random_state=2026)
    search = GridSearchCV(pipeline(), options, scoring={'macro_f1': 'f1_macro', 'accuracy': 'accuracy'},
        refit='macro_f1', cv=folds, n_jobs=args.jobs, verbose=2, error_score='raise', return_train_score=False)
    with threadpool_limits(limits=1):
        search.fit(train['review'], train['sentiment'])
    results = pd.DataFrame(search.cv_results_)
    results.to_csv(output / 'cv_results.csv', index=False)
    best = search.best_estimator_
    joblib.dump(best, output / 'sentiment_model_candidate.pkl')
    # Final comparison after the winning configuration is frozen.
    with threadpool_limits(limits=1):
        baseline = pipeline().fit(train['review'], train['sentiment'])
        baseline_metrics, _ = report(baseline, test['review'], test['sentiment'])
        candidate_metrics, predicted = report(best, test['review'], test['sentiment'])
        saved = joblib.load(HERE / 'sentiment_model.pkl')
        saved_full, _ = report(saved, old_test['legacy_text'], old_test['sentiment'])
        deployed_full, _ = report(saved, old_test['review'], old_test['sentiment'])
        candidate_full, _ = report(best, old_test['review'], old_test['sentiment'])
    errors = test[['row_id', 'review', 'sentiment']].copy()
    errors['predicted'] = predicted
    errors[errors['sentiment'] != errors['predicted']].to_csv(output / 'misclassified_reviews.csv', index=False)
    test.assign(predicted=predicted).to_csv(output / 'holdout_predictions.csv', index=False)
    selection = {key: str(value) if key == 'features' else value for key, value in search.best_params_.items()}
    summary = {'audit': audit, 'selection_metric': 'training CV macro F1', 'folds': args.folds,
        'candidate_count': len(results), 'best_cv_macro_f1': search.best_score_, 'best_parameters': selection,
        'baseline_clean_holdout': baseline_metrics, 'candidate_clean_holdout': candidate_metrics,
        'saved_model_legacy_holdout': saved_full, 'saved_model_raw_input_holdout': deployed_full,
        'candidate_legacy_holdout': candidate_full,
        'versions': {'python': platform.python_version(), 'sklearn': sklearn.__version__, 'pandas': pd.__version__, 'numpy': np.__version__},
        'active_model_sha256': hashlib.sha256((HERE / 'sentiment_model.pkl').read_bytes()).hexdigest(),
        'elapsed_seconds': time.monotonic() - start,
        'activation': 'Candidate only. sentiment_model.pkl was not replaced.'}
    (output / 'evaluation.json').write_text(json.dumps(summary, indent=2), encoding='utf-8')
    lines = ['# Logistic Regression retraining results', '',
        '| Evaluation | Accuracy | Macro F1 | Neutral F1 |', '|---|---:|---:|---:|']
    for name, metrics in [('Old saved model / published split (cleaned input)', saved_full),
        ('Old saved model / published split (raw input)', deployed_full),
        ('Tuned candidate / published split', candidate_full),
        ('Baseline / cleaned holdout', baseline_metrics), ('Tuned candidate / cleaned holdout', candidate_metrics)]:
        lines.append(f"| {name} | {metrics['accuracy']:.2%} | {metrics['macro_f1']:.2%} | {metrics['classification_report']['neutral']['f1-score']:.2%} |")
    lines += ['', f'Training-only {args.folds}-fold CV macro F1: {search.best_score_:.2%}.', '',
        audit['evaluation_note'], '', audit['domain_note'], '',
        'Candidate and reports are saved separately. No labels were automatically changed. The active model was not replaced.',
        '', 'See audit.json, evaluation.json, cv_results.csv, split_manifest.csv and misclassified_reviews.csv for reproducibility and manual label review.']
    (output / 'REPORT.md').write_text('\n'.join(lines) + '\n', encoding='utf-8')
    print('\n'.join(lines), flush=True)
    print(f'Artifacts: {output}', flush=True)


if __name__ == '__main__':
    main()
