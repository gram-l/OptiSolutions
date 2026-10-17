"""Verify a saved candidate in a fresh process and export its evaluation table."""
import argparse
import csv
import hashlib
import json
import os
import sys
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'sentiment_service.settings')

import joblib
import numpy as np
import pandas as pd
from sklearn.metrics import accuracy_score, f1_score
from threadpoolctl import threadpool_limits


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('run_directory', type=Path)
    args = parser.parse_args()
    run = args.run_directory
    evaluation = json.loads((run / 'evaluation.json').read_text(encoding='utf-8'))
    candidate = joblib.load(run / 'sentiment_model_candidate.pkl')
    holdout = pd.read_csv(run / 'holdout_predictions.csv')
    with threadpool_limits(limits=1):
        predicted = candidate.predict(holdout['review'])
    np.testing.assert_array_equal(predicted, holdout['predicted'])
    assert abs(accuracy_score(holdout['sentiment'], predicted) - evaluation['candidate_clean_holdout']['accuracy']) < 1e-12
    assert abs(f1_score(holdout['sentiment'], predicted, average='macro') - evaluation['candidate_clean_holdout']['macro_f1']) < 1e-12
    assert hashlib.sha256((ROOT / 'ml_training/sentiment_model.pkl').read_bytes()).hexdigest() == evaluation['active_model_sha256']

    from sentiment_api import views
    # Exercise real Django response handling with the serialized candidate;
    # patch only this verification process, never the active model on disk.
    with patch.object(views, 'model', candidate):
        for text in holdout['review'].head(12):
            request = SimpleNamespace(method='POST', body=json.dumps({'feedback_text': text}).encode())
            response = views.predict_sentiment(request)
            assert response.status_code == 200
            result = json.loads(response.content)
            assert result['sentiment_label'].lower() == candidate.predict([text])[0]
            assert 0 <= result['confidence_score'] <= 1

    report = evaluation['candidate_clean_holdout']['classification_report']
    rows = []
    for key, display in [('negative', 'Negative'), ('neutral', 'Neutral'), ('positive', 'Positive'),
        ('macro avg', 'Macro Average'), ('weighted avg', 'Weighted Average')]:
        values = report[key]
        rows.append([display, f"{values['precision']:.2%}", f"{values['recall']:.2%}", f"{values['f1-score']:.2%}", int(values['support'])])
    rows.append(['Accuracy', '', '', f"{evaluation['candidate_clean_holdout']['accuracy']:.2%}", len(holdout)])
    with (run / 'performance_table.csv').open('w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        writer.writerow(['Class/Average', 'Precision', 'Recall', 'F1-Score / Accuracy', 'Support'])
        writer.writerows(rows)
    table = ['# Performance Evaluation — retrained Logistic Regression', '',
        '| Class/Average | Precision | Recall | F1-Score | Support |', '|---|---:|---:|---:|---:|']
    table.extend('| ' + ' | '.join(map(str, row)) + ' |' for row in rows[:-1])
    table += ['', f"Accuracy: **{evaluation['candidate_clean_holdout']['accuracy']:.2%}** on {len(holdout):,} reviews.", '',
        'Selected using training-only five-fold cross-validation. No holdout samples were used for tuning. '
        'This is the previously reported holdout, not newly collected independent data. '
        'Training duplicates and training/test text overlaps were removed; no labels were automatically changed.', '',
        'Source: [FiReCS by ccosme](https://huggingface.co/datasets/ccosme/FiReCS), CC BY 4.0. '
        f"Snapshot: `{evaluation['audit']['data_snapshot']}`. This is a product-review benchmark; clinic accuracy still needs a manually labeled clinic test set.", '',
        'The candidate remains separate from the model loaded by the app.']
    (run / 'PERFORMANCE_TABLE.md').write_text('\n'.join(table) + '\n', encoding='utf-8')
    print('Verified: serialized predictions reproduce all 2,098 holdout results; 12 real endpoint responses match; active model unchanged.')
    print('\n'.join(table))


if __name__ == '__main__':
    main()
