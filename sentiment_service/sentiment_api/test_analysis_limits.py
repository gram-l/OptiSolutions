import json
import os
import unittest
from types import SimpleNamespace
from unittest.mock import patch

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'sentiment_service.settings')

from .limits import MAX_TEXT_LENGTH, MAX_BATCH_ITEMS, MAX_REQUEST_BYTES
from . import views


class AnalysisLimitTests(unittest.TestCase):
    def call(self, view, payload):
        return view(SimpleNamespace(method='POST', body=json.dumps(payload).encode()))

    def test_long_text_is_rejected_before_any_analysis(self):
        with patch.object(views, 'model') as model, patch.object(views, 'classify_issue') as classify, patch.object(views, 'category_matches') as matches:
            text = 'x' * (MAX_TEXT_LENGTH + 1)
            for view, payload in [
                (views.predict_sentiment, {'feedback_text': text}),
                (views.categorize_complaint, {'complaint_text': text}),
                (views.diagnose_root_causes, {'items': [{'id': 1, 'source': 'feedback', 'text': text}]}),
            ]:
                self.assertEqual(self.call(view, payload).status_code, 400)
            model.predict.assert_not_called()
            classify.assert_not_called()
            matches.assert_not_called()

    def test_text_at_the_character_limit_is_accepted_without_truncation(self):
        text = 'x' * MAX_TEXT_LENGTH
        with patch.object(views, 'classify_issue', return_value='other') as classify:
            self.assertEqual(self.call(views.categorize_complaint, {'complaint_text': text}).status_code, 200)
            classify.assert_called_once_with(text)

    def test_batch_limit_is_checked_before_processing(self):
        items = [{'id': i, 'source': 'feedback', 'text': 'No issue'} for i in range(MAX_BATCH_ITEMS + 1)]
        with patch.object(views, 'category_matches') as matches:
            self.assertEqual(self.call(views.diagnose_root_causes, {'items': items}).status_code, 400)
            matches.assert_not_called()
        with patch.object(views, 'category_matches', return_value=[]), patch.object(views, 'issue_findings', return_value=[]):
            result = self.call(views.diagnose_root_causes, {'items': items[:MAX_BATCH_ITEMS]})
            self.assertEqual(result.status_code, 200)
            self.assertEqual(len(json.loads(result.content)['details']), MAX_BATCH_ITEMS)

    def test_request_size_boundary_in_bytes(self):
        raw = json.dumps({'complaint_text': 'A valid complaint'}).encode()
        padded = raw + b' ' * (MAX_REQUEST_BYTES - len(raw))
        with patch.object(views, 'classify_issue', return_value='other'):
            self.assertEqual(views.categorize_complaint(SimpleNamespace(method='POST', body=padded)).status_code, 200)
        for view in [views.predict_sentiment, views.categorize_complaint, views.diagnose_root_causes]:
            self.assertEqual(view(SimpleNamespace(method='POST', body=padded + b' ')).status_code, 413)

    def test_large_declared_size_is_rejected_without_reading_the_body(self):
        class LargeRequest:
            method = 'POST'
            META = {'CONTENT_LENGTH': str(MAX_REQUEST_BYTES + 1)}

            @property
            def body(self):
                raise AssertionError('The oversized body should not be read')

        for view in [views.predict_sentiment, views.categorize_complaint, views.diagnose_root_causes]:
            self.assertEqual(view(LargeRequest()).status_code, 413)

    def test_malformed_json_and_invalid_utf8_fail_cleanly(self):
        for raw in [b'not json', b'\xff', b'[' * 1100 + b']' * 1100]:
            for view in [views.predict_sentiment, views.categorize_complaint, views.diagnose_root_causes]:
                self.assertEqual(view(SimpleNamespace(method='POST', body=raw)).status_code, 400)

    def test_categorization_rejects_non_text_input_without_server_errors(self):
        for payload in [[], {}, {'complaint_text': []}, {'complaint_text': 5}, {'complaint_text': None}]:
            self.assertEqual(self.call(views.categorize_complaint, payload).status_code, 400)
