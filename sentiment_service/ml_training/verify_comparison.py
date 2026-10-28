"""Independently recompute comparison metrics from saved predictions and models."""
import argparse
import hashlib
import json
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from threadpoolctl import threadpool_limits
from feedback_train import HERE, LABELS, data_directory, prepare


def main(run):
    summary = json.loads((run / 'comparison.json').read_text(encoding='utf-8'))
    predictions = pd.read_csv(run / 'all_holdout_predictions.csv')
    _, _, _, train, test, _ = prepare(data_directory(None))
    np.testing.assert_array_equal(predictions['row_id'], test['row_id'])
    np.testing.assert_array_equal(predictions['sentiment'], test['sentiment'])
    folds = pd.read_csv(run / 'cv_fold_manifest.csv')
    assert len(folds) == len(train) == folds['row_id'].nunique()
    assert sorted(folds['validation_fold'].unique()) == [1, 2, 3, 4, 5]
    assert not set(train['legacy_text']) & set(test['legacy_text'])
    reference_features = None
    for slug, metrics in summary['models'].items():
        model = joblib.load(run / f'{slug}.pkl')
        with threadpool_limits(limits=1):
            np.testing.assert_array_equal(model.predict(test['review']), predictions[slug])
        features = model.named_steps['features'].get_feature_names_out()
        if reference_features is None:
            reference_features = features
        else:
            np.testing.assert_array_equal(reference_features, features)
        actual_report = classification_report(test['sentiment'], predictions[slug], labels=LABELS, output_dict=True, zero_division=0)
        for label, values in actual_report.items():
            if isinstance(values, dict):
                for metric, value in values.items():
                    assert abs(value - metrics['classification_report'][label][metric]) < 1e-12
        matrix = confusion_matrix(test['sentiment'], predictions[slug], labels=LABELS)
        np.testing.assert_array_equal(matrix, metrics['confusion_matrix'])
        assert int(matrix.sum()) == 2098
        np.testing.assert_array_equal(matrix.sum(axis=1), [682, 727, 689])
        assert int(np.trace(matrix)) == metrics['correct']
        assert metrics['correct'] + metrics['incorrect'] == 2098
        assert abs(accuracy_score(test['sentiment'], predictions[slug]) - metrics['accuracy']) < 1e-12
        cv = pd.read_csv(run / f'{slug}_cv_results.csv')
        assert len(cv) == 8
        assert abs(cv['mean_test_macro_f1'].max() - metrics['cv_macro_f1_mean']) < 1e-12
        assert abs(np.mean(metrics['cv_fold_macro_f1']) - metrics['cv_macro_f1_mean']) < 1e-12
        assert abs(np.std(metrics['cv_fold_macro_f1']) - metrics['cv_macro_f1_std']) < 1e-12
        # Export an unambiguous report: accuracy belongs in its own column.
        rows = [{'class_or_average': label, **values} for label, values in actual_report.items() if isinstance(values, dict)]
        rows.append({'class_or_average': 'accuracy', 'accuracy': metrics['accuracy'], 'support': 2098})
        pd.DataFrame(rows).to_csv(run / f'{slug}_classification_report.csv', index=False)
    assert hashlib.sha256((HERE / 'sentiment_model.pkl').read_bytes()).hexdigest() == summary['active_model_sha256']
    checks = ['identical test rows and labels', 'zero train/test normalized-text overlap',
        'same five validation folds and eight candidates per model', 'identical fitted feature names',
        'all serialized model predictions reproduced', 'all class and aggregate metrics recomputed',
        'all confusion matrices and correct counts recomputed', 'CV means and standard deviations verified', 'active model unchanged']
    (run / 'verification.json').write_text(json.dumps({'passed': True, 'checks': checks}, indent=2), encoding='utf-8')
    print('Passed: ' + '; '.join(checks))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('run_directory', type=Path)
    main(parser.parse_args().run_directory)
