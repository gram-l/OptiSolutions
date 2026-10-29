import json
import os
import unittest
from types import SimpleNamespace

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'sentiment_service.settings')

from .issue_evidence import REASON_RULES, _reported_reason, issue_findings
from .issue_categories import ISSUE_CATEGORIES


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
        self.assertEqual(self.finding('masungit ang staff', 'staff_attitude')['reported_reason'], 'Rude or unfriendly behavior reported')

    def test_reason_rules_only_use_the_existing_sixteen_categories(self):
        self.assertEqual(set(REASON_RULES), set(ISSUE_CATEGORIES))
        self.assertEqual(len(ISSUE_CATEGORIES), 16)

    def test_explicit_reported_details_for_existing_concerns(self):
        cases = [
            ('Ang tagal ng pila sa cashier', 'waiting_time', 'Long wait at cashier reported'),
            ('Sobrang mahal ng consultation fee', 'consultation_fees', 'High price or fee reported'),
            ('Hindi nabigay ang medical certificate', 'medical_certificate', 'Certificate not provided or delayed reported'),
            ('Minadali ang consultation at hindi nakinig ang doctor sa concern ko', 'doctor_expertise', 'Rushed consultation reported'),
            ('Sana sabihin niyo kung wala yung doctor', 'doctor_availability', 'Advance notice of doctor absence requested'),
            ('Walang abiso na walang doctor', 'doctor_availability', 'No advance notice reported'),
            ('Fully booked at walang available na schedule', 'appointment_scheduling', 'No available slots reported'),
            ('Hindi ako pinansin at walang tumulong', 'staff_attentiveness', 'Lack of assistance reported'),
            ('Walang resibo sa payment ko', 'billing_payment', 'No receipt issued reported'),
            ('Matagal ang resulta ng blood test', 'lab_results', 'Delayed results reported'),
            ('Iba-iba ang sinabi ng staff tungkol sa requirements', 'communication_information', 'Inconsistent or wrong information reported'),
            ('Nag-error ang website habang online booking', 'online_system', 'Website error reported'),
            ('Siksikan sa waiting area at kulang ang upuan', 'cleanliness_hygiene', 'Overcrowded waiting area reported'),
            ('Madumi ang banyo', 'cleanliness_hygiene', 'Dirty or smelly restroom reported'),
            ('Narinig ng ibang pasyente ang usapan', 'privacy_confidentiality', 'Private information overheard or exposed reported'),
            ('Walang ramp para sa wheelchair', 'accessibility_facility', 'No wheelchair ramp or access reported'),
            ('Sarado ang clinic kahit within operating hours pa', 'operating_hours', 'Closed during posted hours reported'),
        ]
        for text, category, expected in cases:
            with self.subTest(text=text):
                finding = self.finding(text, category)
                self.assertEqual(finding['reported_reason'], expected)
                self.assertIn(finding['evidence'], text)

    def test_additional_complaint_wording_uses_existing_categories(self):
        cases = [
            ('My appointment started much later than the scheduled time.', 'waiting_time', 'Late start reported'),
            ('mabagal ang mga staff..', 'waiting_time', 'Slow staff reported'),
            ('The laboratory results took longer than expected to be released.', 'lab_results', 'Delayed results reported'),
            ('The online appointment booking system was difficult to use.', 'online_system', 'Difficult to use reported'),
            ('The consultation room was not clean upon arrival.', 'cleanliness_hygiene', 'Room not clean on arrival reported'),
            ('The receptionist was not accommodating during check-in.', 'staff_attentiveness', 'Staff not accommodating or helpful reported'),
            ('The doctor did not clearly explain my diagnosis and treatment.', 'communication_information', 'Unclear explanation or instructions reported'),
            ('The waiting area was overcrowded and lacked enough seating.', 'cleanliness_hygiene', 'Overcrowded waiting area reported'),
            ('The clinic had insufficient seating.', 'cleanliness_hygiene', 'Insufficient seating reported'),
            ('Hindi magaling ang doctor', 'doctor_expertise', 'Dissatisfaction with doctor expertise reported'),
            ('Masama ang ugali ng staff', 'staff_attitude', 'Negative staff attitude reported'),
            ('The restroom needed cleaning.', 'cleanliness_hygiene', 'Restroom cleaning or maintenance needed reported'),
        ]
        for text, category, expected in cases:
            with self.subTest(text=text):
                finding = self.finding(text, category)
                self.assertEqual(finding['reported_reason'], expected)
                self.assertIn(finding['evidence'], text)

    def test_generic_phrases_do_not_invent_subjects_or_problems(self):
        cases = [
            ('lab_results', 'Waiting took longer than expected, but the laboratory results arrived on time'),
            ('lab_results', 'The consultation took longer than expected'),
            ('online_system', 'The chair was difficult to use but the website worked well'),
            ('staff_attentiveness', 'The receptionist was accommodating during check in'),
            ('staff_attentiveness', 'I was at reception for check in'),
            ('cleanliness_hygiene', 'The restroom needed'),
            ('cleanliness_hygiene', 'The equipment was not clean upon arrival'),
        ]
        for category, text in cases:
            with self.subTest(text=text):
                self.assertEqual(_reported_reason(category, text)[1], 'Unspecified')

    def test_new_phrases_respect_negation_and_adverbs(self):
        for text in ['The staff were not really rude', 'Hindi po masama ang ugali ng staff']:
            with self.subTest(text=text):
                self.assertNotIn('staff_attitude', [finding['category'] for finding in issue_findings(text)])
        cases = [
            ('online_system', 'The online appointment booking system was not difficult to use'),
            ('staff_attentiveness', 'The receptionist was not really not accommodating'),
            ('doctor_expertise', 'He is not really not a good doctor'),
        ]
        for category, text in cases:
            with self.subTest(text=text):
                self.assertEqual(_reported_reason(category, text)[1], 'Unspecified')
        self.assertEqual(self.finding('The doctor did not properly explain the instructions', 'communication_information')['reported_reason'], 'Unclear explanation or instructions reported')

    def test_new_rule_categories_are_not_silently_introduced(self):
        self.assertNotIn('waiting_area_comfort', ISSUE_CATEGORIES)
        self.assertNotIn('pharmacy_medicine', ISSUE_CATEGORIES)
        self.assertEqual(len(ISSUE_CATEGORIES), 16)

    def test_vague_and_negated_details_remain_unspecified(self):
        self.assertEqual(self.finding('need improvement sa service and scheduling', 'appointment_scheduling')['reported_reason'], 'Unspecified')
        self.assertEqual(self.finding('Madumi ang clinic pero not rude. Hindi mabaho ang banyo', 'cleanliness_hygiene')['reported_reason'], 'Unspecified')
        self.assertEqual(self.finding('Sarado ang clinic kahit gusto kong pumunta', 'operating_hours')['reported_reason'], 'Unspecified')
        for category, text in [
            ('cleanliness_hygiene', 'Hindi madumi ang banyo'),
            ('doctor_expertise', 'Hindi minadali ang consultation'),
            ('consultation_fees', 'Hindi mahal ang fee'),
            ('billing_payment', 'It is not no receipt'),
            ('operating_hours', 'We were not closed during operating hours'),
        ]:
            with self.subTest(text=text):
                self.assertEqual(_reported_reason(category, text)[1], 'Unspecified')

    def test_no_restroom_problem_is_invented_from_a_general_clinic_comment(self):
        finding = self.finding('Madumi ang clinic. Hindi madumi ang banyo.', 'cleanliness_hygiene')
        self.assertEqual(finding['reported_reason'], 'Unspecified')

    def test_lab_result_non_delivery_is_not_rewritten_as_certificate_non_delivery(self):
        for finding in issue_findings('Hindi nabigay ang resulta'):
            self.assertNotIn('Certificate', finding['reported_reason'])

    def test_later_explicit_reason_is_preferred_over_generic_first_sentence(self):
        text = 'Matagal. Naghintay ako dahil late dumating ang doctor.'
        finding = self.finding(text)
        self.assertIn('attributes waiting', finding['reported_reason'])
        self.assertEqual(finding['evidence'], 'Naghintay ako dahil late dumating ang doctor')
        self.assertIn(finding['evidence'], text)

    def test_explicit_attribution_is_preferred_over_an_earlier_descriptive_detail(self):
        text = 'Ang tagal ng pila sa cashier. Naghintay ako dahil late dumating ang doctor.'
        self.assertEqual(self.finding(text)['reported_reason'], 'Patient attributes waiting to late doctor arrival')

    def test_descriptions_require_the_reported_subject_and_preserve_requests(self):
        self.assertEqual(_reported_reason('doctor_expertise', 'Minadali ang pagprint ng resibo')[1], 'Unspecified')
        self.assertEqual(_reported_reason('medical_certificate', 'Hindi malinaw ang requirements')[1], 'Unspecified')
        self.assertEqual(self.finding('Sarado ang clinic, sana updated ang posted schedule', 'operating_hours')['reported_reason'], 'Posted schedule update requested')

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
