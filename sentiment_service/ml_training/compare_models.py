"""Controlled comparison of LR, Multinomial NB and Linear SVM.

All classifiers use identical cleaned rows, fixed light TF-IDF features,
five training folds and eight parameter candidates. Test labels never select
hyperparameters. Artifacts do not replace the app's active model.
"""
import os
os.environ.setdefault('OMP_NUM_THREADS', '1')
os.environ.setdefault('OPENBLAS_NUM_THREADS', '1')
os.environ.setdefault('MKL_NUM_THREADS', '1')
import argparse
import hashlib
import json
import platform
import time
import warnings
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
import sklearn
from sklearn.exceptions import ConvergenceWarning
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import GridSearchCV, StratifiedKFold
from sklearn.naive_bayes import MultinomialNB
from sklearn.svm import LinearSVC
from threadpoolctl import threadpool_limits
from feedback_train import HERE, LABELS, data_directory, pipeline, prepare, report


def specification():
    return [
        ('logistic_regression', 'Logistic Regression', LogisticRegression(solver='lbfgs', max_iter=2000, random_state=42),
            {'clf__C': [0.5, 1.0, 4.0, 16.0], 'clf__class_weight': [None, 'balanced']}),
        ('multinomial_naive_bayes', 'Multinomial Naive Bayes', MultinomialNB(),
            {'clf__alpha': [0.01, 0.03, 0.1, 0.3, 0.5, 1.0, 2.0, 5.0]}),
        ('linear_svm', 'Linear SVM', LinearSVC(dual='auto', max_iter=10000, random_state=42),
            {'clf__C': [0.5, 1.0, 4.0, 16.0], 'clf__class_weight': [None, 'balanced']}),
    ]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--data-dir')
    parser.add_argument('--output-dir', type=Path, required=True)
    args = parser.parse_args()
    output = args.output_dir.resolve()
    output.mkdir(parents=True, exist_ok=False)
    started = time.monotonic()
    frame, _, _, train, test, audit = prepare(data_directory(args.data_dir))
    previous_run = HERE / 'experiments/lr_20261017'
    manifest = frame[['row_id', 'source_split', 'sentiment']].copy()
    manifest['partition'] = 'excluded'
    manifest.loc[train.index, 'partition'] = 'train_cv'
    manifest.loc[test.index, 'partition'] = 'test'
    pd.testing.assert_frame_equal(manifest, pd.read_csv(previous_run / 'split_manifest.csv'))
    manifest.to_csv(output / 'split_manifest.csv', index=False)
    folds = list(StratifiedKFold(n_splits=5, shuffle=True, random_state=2026).split(train['review'], train['sentiment']))
    assignments = []
    for fold_number, (fit_rows, valid_rows) in enumerate(folds, 1):
        assert not set(train.iloc[fit_rows]['legacy_text']) & set(train.iloc[valid_rows]['legacy_text'])
        assignments.extend({'row_id': int(train.iloc[index]['row_id']), 'validation_fold': fold_number} for index in valid_rows)
    pd.DataFrame(assignments).sort_values('row_id').to_csv(output / 'cv_fold_manifest.csv', index=False)
    model_path = HERE / 'sentiment_model.pkl'
    active_hash = hashlib.sha256(model_path.read_bytes()).hexdigest()
    summary = {
        'audit': audit, 'cv_folds': 5, 'cv_seed': 2026, 'selection_metric': 'mean training CV macro F1',
        'candidate_settings_per_model': 8,
        'features': {'preprocessing': 'light', 'word_ngram_range': [1, 2], 'word_max_features': 20000,
            'char_analyzer': 'char_wb', 'char_ngram_range': [3, 5], 'char_max_features': 30000,
            'min_df': 2, 'sublinear_tf': True},
        'methodology_note': 'Fixed common feature specification is the prior LR training-CV winner. This is a controlled classifier comparison, not independent exhaustive feature optimization for each algorithm. '
            'The previously reported holdout is reused for final evaluation and is not a fresh external benchmark. '
            'No models are selected or retuned using holdout results. CV standard deviations describe variation across these five folds, not confidence intervals.',
        'versions': {'python': platform.python_version(), 'sklearn': sklearn.__version__, 'pandas': pd.__version__, 'numpy': np.__version__},
        'timing_note': 'Measured on this computer with one numerical thread. Prediction time includes preprocessing and TF-IDF; median of five full-holdout batches after one warm-up. Timings are not guaranteed VPS performance.',
        'active_model_sha256': active_hash,
        'models': {},
    }
    predictions = test[['row_id', 'sentiment']].copy()
    # Finish selection for ALL models before evaluating ANY model on the holdout.
    selected = {}
    with threadpool_limits(limits=1), warnings.catch_warnings():
        warnings.simplefilter('error', ConvergenceWarning)
        for slug, name, estimator, grid in specification():
            print(f'Tuning {name}: 8 candidates x 5 identical folds', flush=True)
            model = pipeline(mode='light', rich=True).set_params(clf=estimator)
            search = GridSearchCV(model, grid, cv=folds,
                scoring={'macro_f1': 'f1_macro', 'accuracy': 'accuracy'}, refit='macro_f1',
                n_jobs=1, error_score='raise', verbose=1)
            search_start = time.monotonic()
            search.fit(train['review'], train['sentiment'])
            search_seconds = time.monotonic() - search_start
            cv = pd.DataFrame(search.cv_results_)
            cv.to_csv(output / f'{slug}_cv_results.csv', index=False)
            selected[slug] = search.best_estimator_
            joblib.dump(search.best_estimator_, output / f'{slug}.pkl')
            best = search.best_index_
            summary['models'][slug] = {
                'name': name, 'best_parameters': search.best_params_,
                'cv_macro_f1_mean': float(cv.loc[best, 'mean_test_macro_f1']),
                'cv_macro_f1_std': float(cv.loc[best, 'std_test_macro_f1']),
                'cv_accuracy_mean': float(cv.loc[best, 'mean_test_accuracy']),
                'cv_accuracy_std': float(cv.loc[best, 'std_test_accuracy']),
                'cv_fold_macro_f1': [float(cv.loc[best, f'split{i}_test_macro_f1']) for i in range(5)],
                'search_wall_seconds': search_seconds, 'refit_seconds': search.refit_time_,
            }
            print(f'{name}: selected {search.best_params_}, CV macro F1 {search.best_score_:.4%}', flush=True)
        print('All settings frozen. Evaluating the common holdout.', flush=True)
        for slug, model in selected.items():
            metrics, predicted = report(model, test['review'], test['sentiment'])
            np.testing.assert_array_equal(joblib.load(output / f'{slug}.pkl').predict(test['review']), predicted)
            predictions[slug] = predicted
            latencies = []
            for _ in range(5):
                timer = time.perf_counter()
                model.predict(test['review'])
                latencies.append(time.perf_counter() - timer)
            metrics.update({'correct': int(np.sum(predicted == test['sentiment'].to_numpy())),
                'incorrect': int(np.sum(predicted != test['sentiment'].to_numpy())),
                'prediction_batch_seconds_median': float(np.median(latencies)),
                'prediction_ms_per_review': float(np.median(latencies) * 1000 / len(test))})
            summary['models'][slug].update(metrics)
            errors = test[['row_id', 'review', 'sentiment']].copy()
            errors['predicted'] = predicted
            errors.loc[errors['sentiment'] != errors['predicted']].to_csv(output / f'{slug}_misclassified.csv', index=False)
            pd.DataFrame(metrics['confusion_matrix'], index=LABELS, columns=LABELS).to_csv(output / f'{slug}_confusion_matrix.csv')
            print(f"{summary['models'][slug]['name']}: accuracy {metrics['accuracy']:.4%}, macro F1 {metrics['macro_f1']:.4%}, correct {metrics['correct']}/{len(test)}", flush=True)
    predictions.to_csv(output / 'all_holdout_predictions.csv', index=False)
    assert hashlib.sha256(model_path.read_bytes()).hexdigest() == active_hash
    summary['elapsed_seconds'] = time.monotonic() - started
    summary['activation'] = 'Comparison only; active model and VPS unchanged.'
    (output / 'comparison.json').write_text(json.dumps(summary, indent=2), encoding='utf-8')
    (output / 'audit.json').write_text(json.dumps(audit, indent=2), encoding='utf-8')
    rows = []
    for slug, metrics in summary['models'].items():
        row = {'model': metrics['name'], 'accuracy': metrics['accuracy'],
            'macro_precision': metrics['classification_report']['macro avg']['precision'],
            'macro_recall': metrics['classification_report']['macro avg']['recall'],
            'macro_f1': metrics['macro_f1'], 'weighted_f1': metrics['classification_report']['weighted avg']['f1-score'],
            'neutral_f1': metrics['classification_report']['neutral']['f1-score'], 'correct': metrics['correct'],
            'cv_macro_f1_mean': metrics['cv_macro_f1_mean'], 'cv_macro_f1_std': metrics['cv_macro_f1_std'],
            'refit_seconds': metrics['refit_seconds'], 'prediction_ms_per_review': metrics['prediction_ms_per_review']}
        rows.append(row)
        class_rows = [{'class_or_average': label, **values} for label, values in metrics['classification_report'].items() if isinstance(values, dict)]
        class_rows.append({'class_or_average': 'accuracy', 'accuracy': metrics['accuracy'], 'support': len(test)})
        pd.DataFrame(class_rows).to_csv(output / f'{slug}_classification_report.csv', index=False)
    pd.DataFrame(rows).to_csv(output / 'comparison_summary.csv', index=False)
    print(f'Complete. Artifacts: {output}', flush=True)


if __name__ == '__main__':
    main()
