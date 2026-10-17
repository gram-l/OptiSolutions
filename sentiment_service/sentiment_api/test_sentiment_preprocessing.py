import json
import os
import tempfile
import unittest
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import Mock, patch

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'sentiment_service.settings')

import joblib
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import FunctionTransformer
from .text_preprocessing import model_inputs, normalize_text, normalize_texts


class PreprocessingTests(unittest.TestCase):
    def test_light_cleaning_preserves_contrast_negation_and_unicode(self):
        self.assertEqual(normalize_text('  Maganda PERO hindi mabilis! 5/5 😊  '), 'maganda pero hindi mabilis! 5/5 😊')
        self.assertEqual(normalize_text('not rude &amp; helpful https://example.com'), 'not rude & helpful')
        self.assertEqual(normalize_texts([None, '', '  OK  ']), ['', '', 'ok'])

    def test_legacy_cleaning_matches_the_training_csv_rules(self):
        self.assertEqual(normalize_text('The service is good pero hindi mabilis. 5/5', 'legacy'), 'service good hindi mabilis')
        self.assertEqual(normalize_text('Salamat po https://example.com', 'legacy'), 'salamat')

    def test_new_pipelines_do_not_get_legacy_cleaning_applied_twice(self):
        text = 'Good pero not fast!'
        self.assertEqual(model_inputs(SimpleNamespace(named_steps={}), [text]), ['good not fast'])
        self.assertEqual(model_inputs(SimpleNamespace(named_steps={'preprocess': object()}), [text]), [text])

    def test_saved_pipeline_restores_preprocessing_and_predictions(self):
        texts = ['great helpful service', 'excellent friendly doctor', 'awful rude staff', 'terrible long wait', 'ordinary service', 'neither good nor bad']
        model = Pipeline([('preprocess', FunctionTransformer(normalize_texts, kw_args={'mode': 'light'})),
            ('features', TfidfVectorizer()), ('clf', LogisticRegression())]).fit(texts, ['positive', 'positive', 'negative', 'negative', 'neutral', 'neutral'])
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / 'pipeline.pkl'
            joblib.dump(model, path)
            restored = joblib.load(path)
            np.testing.assert_array_equal(model.predict(['  GREAT helpful SERVICE  ']), restored.predict(['  GREAT helpful SERVICE  ']))


class PredictionEndpointTests(unittest.TestCase):
    def predict(self, payload):
        from .views import predict_sentiment
        return predict_sentiment(SimpleNamespace(method='POST', body=json.dumps(payload).encode()))

    def test_legacy_endpoint_uses_the_same_cleaning_as_training(self):
        with patch('sentiment_api.views.model') as model:
            model.named_steps = {}
            model.predict.return_value = ['positive']
            model.predict_proba.return_value = [[0.1, 0.2, 0.7]]
            response = self.predict({'feedback_text': 'The service is good pero hindi mabilis.'})
            self.assertEqual(response.status_code, 200)
            model.predict.assert_called_once_with(['service good hindi mabilis'])
            self.assertEqual(json.loads(response.content)['sentiment_label'], 'Positive')

    def test_new_pipeline_receives_raw_text_and_handles_its_own_preprocessing(self):
        with patch('sentiment_api.views.model') as model:
            model.named_steps = {'preprocess': Mock()}
            model.predict.return_value = ['neutral']
            model.predict_proba.return_value = [[0.2, 0.6, 0.2]]
            response = self.predict({'feedback_text': 'Good pero not fast!'})
            self.assertEqual(response.status_code, 200)
            model.predict.assert_called_once_with(['Good pero not fast!'])

    def test_missing_null_and_non_text_reviews_are_rejected_without_prediction(self):
        with patch('sentiment_api.views.model') as model:
            model.named_steps = {}
            for payload in [{}, {'feedback_text': None}, {'feedback_text': 5}, {'feedback_text': '   '}, [], {'feedback_text': 'the is po'}]:
                self.assertEqual(self.predict(payload).status_code, 400)
            model.predict.assert_not_called()


if __name__ == '__main__':
    unittest.main()
