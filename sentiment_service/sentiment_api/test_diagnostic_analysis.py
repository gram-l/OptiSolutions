import json
import os
import unittest
from types import SimpleNamespace

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'sentiment_service.settings')

from .issue_evidence import issue_findings


class EvidenceTests(unittest.TestCase):
    def finding(self, text, category='waiting_time'):
        return next(item for item in issue_findings(text) if item['category'] == category)

    def test_causal_patient_statement_has_verbatim_evidence(self):
        text = 'Matagal akong naghintay dahil late dumating ang doctor.'
        finding = self.finding(text)
        self.assertEqual(finding['reported_reason'], 'Patient attributes waiting to late doctor arrival')
        self.assertIn(finding['evidence'], text)

    def test_arrival_alone_and_unrelated_because_do_not_establish_waiting_cause(self):
        for text in ['late dumating ang doctor', 'matagal',
                     'I waited because the queue was long and the doctor arrived late',
                     'Hindi ako naghintay dahil late dumating ang doctor']:
            self.assertNotIn('attributes', self.finding(text)['reported_reason'])

    def test_queue_and_behavior_are_reports_not_underlying_causes(self):
        self.assertIn('underlying reason unspecified', self.finding('ang tagal ng pila')['reported_reason'])
        self.assertIn('attitude concern reported', self.finding('masungit ang staff', 'staff_attitude')['reported_reason'])

    def test_later_explicit_reason_is_preferred_over_generic_first_sentence(self):
        text = 'Matagal. Naghintay ako dahil late dumating ang doctor.'
        finding = self.finding(text)
        self.assertIn('attributes waiting', finding['reported_reason'])
        self.assertEqual(finding['evidence'], 'Naghintay ako dahil late dumating ang doctor')
        self.assertIn(finding['evidence'], text)

    def test_bias_does_not_get_rewritten_as_rudeness(self):
        finding = self.finding('masyadong bias sa kakilala', 'staff_attitude')
        self.assertNotIn('Rude', finding['reported_reason'])
        self.assertIn('bias sa kakilala', finding['evidence'])

    def test_evidence_selects_relevant_sentence_and_negation_is_preserved(self):
        text = 'Malinis ang clinic. Masungit ang staff!'
        self.assertEqual(self.finding(text, 'staff_attitude')['evidence'], 'Masungit ang staff')
        self.assertFalse(issue_findings('The staff is not rude'))


class DiagnosticEndpointTests(unittest.TestCase):
    def call(self, payload, method='POST'):
        from .views import diagnose_root_causes
        return diagnose_root_causes(SimpleNamespace(method=method, body=json.dumps(payload).encode()))

    def test_multi_issue_counts_once_per_record_and_separates_sources(self):
        response = self.call({'items': [
            {'id': 1, 'source': 'feedback', 'text': 'Matagal matagal at masungit ang staff'},
            {'id': 1, 'source': 'complaint', 'text': 'Matagal ang pila'},
            {'id': 2, 'source': 'feedback', 'text': 'No matching concern here'},
        ]})
        self.assertEqual(response.status_code, 200)
        data = json.loads(response.content)
        self.assertEqual(data['feedback_category_counts']['waiting_time'], 1)
        self.assertEqual(data['feedback_category_counts']['staff_attitude'], 1)
        self.assertEqual(data['complaint_category_counts']['waiting_time'], 1)
        self.assertEqual(len(data['details'][0]['findings']), 2)
        self.assertEqual(data['details'][2]['findings'], [])

    def test_empty_batch_and_malformed_requests(self):
        self.assertEqual(self.call({'items': []}).status_code, 200)
        self.assertEqual(self.call({}, method='GET').status_code, 405)
        invalid = [[], {}, {'items': None}, {'items': [None]},
                   {'items': [{'id': 1, 'text': [], 'source': 'feedback'}]},
                   {'items': [{'id': 1, 'text': 'wait', 'source': []}]},
                   {'items': [{'id': True, 'text': 'wait', 'source': 'feedback'}]},
                   {'items': [{'id': 1, 'text': 'wait', 'source': 'feedback'}] * 2}]
        for payload in invalid:
            with self.subTest(payload=payload):
                self.assertEqual(self.call(payload).status_code, 400)
