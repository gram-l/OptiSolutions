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


if __name__ == "__main__":
    unittest.main()
