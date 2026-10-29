"""Conservative, verbatim evidence for lexicon findings; no inferred causes."""

import re

from .issue_categories import _normalize, category_matches


def _reported_reason(category, evidence):
    normalized = _normalize(evidence)
    if category == 'waiting_time':
        # Require waiting and an explicit causal connector in the same sentence.
        waiting = r'\b(?:naghintay|hintay|waiting|waited|wait|pinaghintay)\b'
        late_doctor = r'\b(?:late (?:dumating (?:ang |si )?)?(?:doktor|doctor)|(?:doktor|doctor) (?:arrived|came) late)\b'
        attribution = waiting + r'.{0,60}\b(?:dahil|kasi|because)\s+(?:(?:ang|si|the)\s+)?' + late_doctor
        if (re.search(attribution, normalized)
                and not re.search(r'\b(?:hindi|not|never)\b', normalized)):
            return 2, 'Patient attributes waiting to late doctor arrival'
        elif re.search(r'\b(?:pila|queue|queues)\b', normalized):
            return 1, 'Wait in queue reported; underlying reason unspecified'
    elif category == 'staff_attitude':
        # The category also includes bias and favoritism, not just rudeness.
        return 1, 'Staff attitude concern reported; underlying reason unspecified'
    return 0, 'Unspecified'


def issue_findings(text):
    clauses = [part.strip() for part in re.split(r'[.!?;\n]+', text) if part.strip()]
    # Match each sentence once, keeping the original text for evidence.
    candidates = [(part, category_matches(part)) for part in clauses]
    findings = []
    for category in category_matches(text):
        evidence_options = [part for part, categories in candidates if category in categories] or [text.strip()]
        # Prefer an explicit reason to an earlier, generic mention of the same concern.
        evidence = max(evidence_options, key=lambda part: _reported_reason(category, part)[0])
        _, reason = _reported_reason(category, evidence)
        findings.append({'category': category, 'reported_reason': reason, 'evidence': evidence})
    return findings
