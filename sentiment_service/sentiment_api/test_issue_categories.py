import unittest

from sentiment_api.issue_categories import ISSUE_CATEGORIES, category_matches, classify_issue


class IssueCategoryTests(unittest.TestCase):
    def test_every_category_has_a_reachable_keyword(self):
        self.assertEqual(len(ISSUE_CATEGORIES), 16)
        for category, keywords in ISSUE_CATEGORIES.items():
            with self.subTest(category=category):
                self.assertTrue(any(classify_issue(keyword) == category for keyword in keywords))

    def test_specific_phrases_win_over_generic_keywords(self):
        cases = {
            "Matagal ang resulta": "lab_results",
            "Ang bagal ng website": "online_system",
            "May hidden fee": "billing_payment",
            "Hindi nabigay ang resulta": "lab_results",
            "Sobra ang singil": "billing_payment",
        }
        for text, expected in cases.items():
            with self.subTest(text=text):
                self.assertEqual(classify_issue(text), expected)

    def test_word_boundaries_avoid_accidental_substrings(self):
        self.assertEqual(classify_issue("heart treatment feedback"), "other")

    def test_empty_and_unknown_text_remain_other(self):
        for text in (None, "", "   ", "hello"):
            self.assertEqual(classify_issue(text), "other")

    def test_case_spacing_and_apostrophe_normalization(self):
        self.assertEqual(classify_issue("WALANG   DOKTOR!"), "doctor_availability")
        self.assertEqual(classify_issue("I can’t login"), "online_system")

    def test_analytics_preserves_multiple_issues(self):
        self.assertEqual(set(category_matches("Bastos at walang doktor")), {"staff_attitude", "doctor_availability"})

    def test_existing_complaint_examples(self):
        cases = {
            'need improvement sa service and scheduling': 'appointment_scheduling',
            'mabagal ang mga staff..': 'waiting_time',
            'grabe ang babagal niyo': 'waiting_time',
            'bakit ba ganto mga staff niyo? masyadong bias sa kakilala': 'staff_attitude',
            'Hindi marunong magpaliwanag doctor dito': 'communication_information',
            'Sana naman sabihin niyo kung wala yung doctor para hindi sayang ang punta ko': 'doctor_availability',
        }
        for text, expected in cases.items():
            with self.subTest(text=text):
                self.assertEqual(classify_issue(text), expected)

    def test_staff_word_alone_does_not_imply_attitude_issue(self):
        self.assertNotIn('staff_attitude', category_matches('mabagal ang staff'))

    def test_expanded_english_and_filipino_complaints(self):
        cases = {
            'We waited for hours': 'waiting_time',
            'Sobrang suplada ng receptionist': 'staff_attitude',
            'Consultation is too expensive': 'consultation_fees',
            'Kailangan ko ng medcert': 'medical_certificate',
            'Maling reseta ang binigay': 'doctor_expertise',
            'Doctor is unavailable again': 'doctor_availability',
            'My appointment was a double booking': 'appointment_scheduling',
            'Nobody helped me': 'staff_attentiveness',
            'I was charged twice': 'billing_payment',
            'My test results are missing': 'lab_results',
            'Kulang sa paliwanag': 'communication_information',
            'The app crashed': 'online_system',
            'The toilet is filthy': 'cleanliness_hygiene',
            'They leaked information': 'privacy_confidentiality',
            'There is no wheelchair access': 'accessibility_facility',
            'The clinic opened late': 'operating_hours',
        }
        for text, expected in cases.items():
            with self.subTest(text=text):
                self.assertEqual(classify_issue(text), expected)

    def test_typo_particles_abbreviations_and_punctuation(self):
        cases = {
            'Hndi po pinansin ang pasyente': 'staff_attentiveness',
            'Wala si doktora': 'doctor_availability',
            'Mabgal ang serbisyo': 'waiting_time',
            'Maling sched ang binigay': 'appointment_scheduling',
            'The page won’t load': 'online_system',
            'Hindi po inexplain ang gagawin': 'communication_information',
            'No-receipt issued': 'billing_payment',
        }
        for text, expected in cases.items():
            with self.subTest(text=text):
                self.assertEqual(classify_issue(text), expected)

    def test_negated_attributes_do_not_become_issues(self):
        self.assertNotIn('staff_attitude', category_matches('The staff were not rude'))
        self.assertNotIn('cleanliness_hygiene', category_matches('Hindi po madumi ang clinic'))
        self.assertEqual(classify_issue('staff doctor service hello'), 'other')

    def test_multiple_issues_remain_available_for_analytics(self):
        matches = category_matches('The app crashed and I was charged twice')
        self.assertIn('online_system', matches)
        self.assertIn('billing_payment', matches)


if __name__ == "__main__":
    unittest.main()
