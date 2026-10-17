# Reproduce the Logistic Regression experiment

Use the existing Python environment with the versions pinned in `../requirements.txt`.
From the repository root on Windows:

```powershell
.\sentiment_service\venv\Scripts\python.exe -B -u sentiment_service/ml_training/feedback_train.py --folds 5 --jobs 1
```

The script reads a local cached FiReCS snapshot. Alternatively, pass `--data-dir`
pointing to a directory containing `FiReCS_train_set.csv` and `FiReCS_test_set.csv`.
It verifies that the source rows reproduce the existing processed CSV before
reconstructing its stratified, random-state-42, 80/20 split. No network request
or model replacement occurs during training. Each run creates a new directory
under `experiments/`; an existing output directory is never overwritten.

The original published holdout stays outside tuning. Training duplicates,
conflicting normalized-text groups, and text overlapping the holdout are excluded.
The log records exactly how many rows were removed. Both TF-IDF vocabularies and
the classifier are fitted inside each training fold. Twenty-four settings are
compared with five-fold cross-validation; mean macro F1 determines the winner.

The candidate includes a `preprocess` pipeline step. Django passes raw input to
these new pipelines; older saved pipelines receive the same legacy cleaning as
their original training CSV. Deploy `sentiment_api/text_preprocessing.py` with any
new pickle: its import path is part of model serialization.

For a completed run, verify serialization and export the updated table:

```powershell
.\sentiment_service\venv\Scripts\python.exe -B sentiment_service/ml_training/verify_candidate.py sentiment_service/ml_training/experiments/RUN_DIRECTORY
```

Artifacts include `evaluation.json`, `cv_results.csv`, `audit.json`, row-level split
assignments, predictions, misclassified reviews for manual inspection, and
`sentiment_model_candidate.pkl`. The app's `sentiment_model.pkl` is kept intact.
Label corrections require human review; the script never rewrites labels to
increase a score. Do not tune further using the misclassified holdout examples.

The 2026-10-17 run reproduced the original 79.69% accuracy and 79.82% macro F1.
The selected candidate achieved 81.98% accuracy, 82.12% macro F1, and 75.62%
Neutral F1 on the same 2,098 previously reported held-out reviews. A baseline
refitted on the cleaned training data achieved 79.84% accuracy and 79.96% macro F1.
No fresh independent benchmark was collected. FiReCS contains predominantly
product reviews, so these numbers do not establish clinic-feedback accuracy.

Source: [FiReCS by ccosme](https://huggingface.co/datasets/ccosme/FiReCS), CC BY 4.0,
snapshot `4da2863e53e7260ab6f2dcd2089af5c112d25c80`.
