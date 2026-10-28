"""Generate paper tables and measured interpretations from comparison.json."""
import argparse
import csv
import json
from pathlib import Path


def percent(value):
    return f'{value:.2%}'


def build(run):
    result = json.loads((run / 'comparison.json').read_text(encoding='utf-8'))
    models = result['models']
    order = ['logistic_regression', 'multinomial_naive_bayes', 'linear_svm']
    sections = []
    for number, slug in enumerate(order, 1):
        model = models[slug]
        metrics = model['classification_report']
        rows = []
        for key, label in [('negative', 'Negative'), ('neutral', 'Neutral'), ('positive', 'Positive'),
            ('macro avg', 'Macro Average'), ('weighted avg', 'Weighted Average')]:
            values = metrics[key]
            rows.append([label, percent(values['precision']), percent(values['recall']), percent(values['f1-score']), f"{int(values['support']):,}"])
        ranking = sorted(['negative', 'neutral', 'positive'], key=lambda label: metrics[label]['f1-score'], reverse=True)
        highest, middle, lowest = ranking
        paragraph = (
            f"Table 4-{number} presents the performance of {model['name']} on 2,098 evaluated reviews. "
            f"The model achieved an overall accuracy of {percent(model['accuracy'])}, correctly classifying {model['correct']:,} samples. "
            f"{highest.title()} sentiment obtained the highest F1-score at {percent(metrics[highest]['f1-score'])}, "
            f"followed by {middle} sentiment at {percent(metrics[middle]['f1-score'])} and {lowest} sentiment at {percent(metrics[lowest]['f1-score'])}. "
            f"The lower score for {lowest} sentiment indicates weaker performance in identifying that category relative to the other two categories. "
            f"The macro F1-score of {percent(metrics['macro avg']['f1-score'])} and weighted F1-score of {percent(metrics['weighted avg']['f1-score'])} "
            'provide complementary summaries of performance. Their similar values are consistent with the relatively balanced numbers of samples across the three sentiment categories.'
        )
        sections.append({'number': number, 'title': f"Performance Evaluation of {model['name']}",
            'headers': ['Class/Average', 'Precision', 'Recall', 'F1-Score', 'Support'], 'rows': rows,
            'interpretation': paragraph, 'accuracy': percent(model['accuracy'])})

    best = max(order, key=lambda slug: models[slug]['macro_f1'])
    accuracy_best = max(order, key=lambda slug: models[slug]['accuracy'])
    sorted_models = sorted(order, key=lambda slug: models[slug]['macro_f1'], reverse=True)
    first, second = sorted_models[:2]
    gap = (models[first]['macro_f1'] - models[second]['macro_f1']) * 100
    rows = []
    for slug in order:
        model = models[slug]
        report = model['classification_report']
        rows.append([model['name'], percent(model['accuracy']), percent(report['macro avg']['precision']),
            percent(report['macro avg']['recall']), percent(model['macro_f1']), percent(report['weighted avg']['f1-score'])])
    paragraph = (
        'Table 4-4 compares the three classifiers using the same 2,098 held-out reviews and identical TF-IDF features. '
        f"{models[best]['name']} obtained the highest macro F1-score at {percent(models[best]['macro_f1'])}. "
        f"{models[accuracy_best]['name']} achieved the highest accuracy at {percent(models[accuracy_best]['accuracy'])}. "
        f"The macro F1 difference between {models[first]['name']} and {models[second]['name']} was {gap:.2f} percentage points. "
        'Macro precision, recall, and F1 give equal weight to each sentiment category, whereas weighted F1 accounts for class support. '
        'These results describe the ranking under this feature configuration and test split; no statistical significance test was performed, so the numerical differences alone do not establish a significant advantage.'
    )
    sections.append({'number': 4, 'title': 'Comparison of Sentiment Analysis Models',
        'headers': ['Model', 'Accuracy', 'Macro Precision', 'Macro Recall', 'Macro F1', 'Weighted F1'],
        'rows': rows, 'interpretation': paragraph})

    rows = []
    for slug in order:
        model = models[slug]
        rows.append([model['name'], f"{percent(model['cv_macro_f1_mean'])} ± {model['cv_macro_f1_std'] * 100:.2f} pp",
            f"{model['refit_seconds']:.3f}", f"{model['prediction_ms_per_review']:.3f}"])
    cv_best = max(order, key=lambda slug: models[slug]['cv_macro_f1_mean'])
    fastest = min(order, key=lambda slug: models[slug]['refit_seconds'])
    paragraph = (
        'Table 4-5 summarizes training-only cross-validation and measured computational time. '
        f"{models[cv_best]['name']} achieved the highest mean five-fold macro F1-score at {percent(models[cv_best]['cv_macro_f1_mean'])}. "
        f"{models[fastest]['name']} had the shortest final fitting time at {models[fastest]['refit_seconds']:.3f} seconds. "
        'Each model was given eight parameter settings and the same five folds, for 40 validation fits per classifier. '
        'The reported standard deviation measures variation across folds and is not a confidence interval. '
        'Final fitting time includes fitting the shared feature pipeline on the complete training set; prediction time includes preprocessing and vectorization and is the median of five full-test batches divided by 2,098 reviews. '
        'These timings were measured on the local computer with one numerical thread and do not represent guaranteed VPS response times.'
    )
    sections.append({'number': 5, 'title': 'Cross Validation and Computational Performance',
        'headers': ['Model', 'CV Macro F1 Mean ± SD', 'Final Fit Seconds', 'Prediction ms/review'], 'rows': rows,
        'interpretation': paragraph})

    for number, slug in enumerate(order, 6):
        model = models[slug]
        matrix = model['confusion_matrix']
        totals = [sum(matrix[row][col] for row in range(3)) for col in range(3)]
        rows = [[label.title(), *matrix[index], sum(matrix[index])] for index, label in enumerate(['negative', 'neutral', 'positive'])]
        rows.append(['Total', *totals, 2098])
        paragraph = (
            f"Table 4-{number} shows the confusion matrix for {model['name']}, with rows representing actual labels and columns representing predicted labels. "
            f"The model correctly identified {matrix[0][0]:,} negative, {matrix[1][1]:,} neutral, and {matrix[2][2]:,} positive reviews, "
            f"for {model['correct']:,} correct predictions and {model['incorrect']:,} misclassifications. "
            f"Among the 727 actual neutral reviews, {matrix[1][0]:,} were predicted as negative and {matrix[1][2]:,} as positive. "
            'These counts identify the observed error directions but do not by themselves establish the linguistic causes of those errors.'
        )
        sections.append({'number': number, 'title': f"Confusion Matrix for {model['name']}",
            'headers': ['Actual / Predicted', 'Negative', 'Neutral', 'Positive', 'Total'], 'rows': rows,
            'interpretation': paragraph})

    intro = (
        'This report provides evaluation tables and interpretation paragraphs for Logistic Regression, Multinomial Naive Bayes, and Linear SVM. '
        f"Under the fixed shared feature configuration, {models[best]['name']} obtained the highest test macro F1-score ({percent(models[best]['macro_f1'])}). "
        'The tables are numbered provisionally for Chapter 4 and may be renumbered to match the paper.'
    )
    methodology = (
        'The comparison used 8,374 training reviews and the same 2,098 held-out reviews used in the earlier evaluation '
        '(682 negative, 727 neutral, and 689 positive). Duplicate training records and training/test text overlaps were removed. '
        'All three classifiers used identical light preprocessing, word TF-IDF unigrams and bigrams (up to 20,000 features), '
        'and character TF-IDF n-grams of length 3–5 (up to 30,000 features), with min_df = 2 and sublinear term frequency. '
        'The feature specification was previously chosen using Logistic Regression training cross-validation and was fixed for this controlled classifier comparison. '
        'Both vocabularies were fitted separately within each training fold. Each classifier used the same five stratified folds and eight parameter settings. '
        'Mean validation macro F1 selected each configuration. All three configurations were frozen before any holdout evaluation.'
    )
    settings = []
    for slug in order:
        model = models[slug]
        text = ', '.join(f'{key.removeprefix("clf__")} = {value}' for key, value in model['best_parameters'].items())
        settings.append(f"{model['name']}: {text}.")
    limitations = (
        'The held-out reviews were previously reported and therefore are not a newly collected independent benchmark. '
        'The experiment does not establish which algorithm is best under every possible feature representation or tuning budget. '
        'FiReCS consists predominantly of Taglish product reviews; these scores do not establish performance on clinical feedback. '
        'A separately collected, manually labeled clinic test set is needed for that assessment. '
        'Linear SVM was evaluated for class labels without probability calibration. None of the comparison models replaced the active application model or the VPS deployment.'
    )
    sources = [
        'FiReCS by ccosme, CC BY 4.0: https://huggingface.co/datasets/ccosme/FiReCS',
        f"Dataset snapshot: {result['audit']['data_snapshot']}",
        'Scikit-learn model selection: https://scikit-learn.org/stable/modules/grid_search.html',
        'MultinomialNB: https://scikit-learn.org/stable/modules/generated/sklearn.naive_bayes.MultinomialNB.html',
        'LinearSVC: https://scikit-learn.org/stable/modules/generated/sklearn.svm.LinearSVC.html',
        f"Runtime: Python {result['versions']['python']}, scikit-learn {result['versions']['sklearn']}, pandas {result['versions']['pandas']}, NumPy {result['versions']['numpy']}.",
    ]
    content = {'title': 'Comparison of Sentiment Analysis Models', 'intro': intro,
        'methodology': methodology, 'settings': settings, 'sections': sections,
        'limitations': limitations, 'sources': sources}
    (run / 'report_content.json').write_text(json.dumps(content, indent=2), encoding='utf-8')
    markdown = ['# ' + content['title'], '', intro, '', '## Evaluation Method', '', methodology, '', 'Selected settings:']
    markdown += ['- ' + item for item in settings]
    for section in sections:
        markdown += ['', f"## Table 4-{section['number']} {section['title']}", '',
            '| ' + ' | '.join(section['headers']) + ' |', '|' + '|'.join(['---'] * len(section['headers'])) + '|']
        markdown += ['| ' + ' | '.join(map(str, row)) + ' |' for row in section['rows']]
        if 'accuracy' in section:
            markdown += ['', 'Overall accuracy: **' + section['accuracy'] + '**.']
        markdown += ['', section['interpretation']]
        with (run / f"table_4_{section['number']}.csv").open('w', encoding='utf-8', newline='') as file:
            writer = csv.writer(file)
            writer.writerow(section['headers'])
            writer.writerows(section['rows'])
    markdown += ['', '## Scope and Limitations', '', limitations, '', '## Sources and Reproducibility', '']
    markdown += ['- ' + source for source in sources]
    (run / 'ALL_TABLES_AND_INTERPRETATIONS.md').write_text('\n'.join(markdown) + '\n', encoding='utf-8')
    print(intro)
    print(f"Prepared {len(sections)} tables, eight interpretation paragraphs, CSVs, and report content.")


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('run_directory', type=Path)
    build(parser.parse_args().run_directory)
