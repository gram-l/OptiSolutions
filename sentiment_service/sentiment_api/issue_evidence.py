"""Conservative, verbatim evidence for lexicon findings; no inferred causes."""

import re

from .issue_categories import _PARTICLE, _normalize, _phrase_pattern, category_matches


# These describe stated problems or requests, not independently verified causes.
# More specific descriptions precede broader ones within each existing category.
REASON_RULES = {
    'waiting_time': [
        ('Late start reported', ['started much later', 'later than scheduled', 'later than the scheduled time', 'started late', 'behind schedule', 'late nagsimula']),
        ('Slow staff reported', ['mabagal ang staff', 'mabagal ang mga staff', 'slow staff']),
        ('Long wait at cashier reported', ['tagal ng pila sa cashier', 'long wait at cashier', 'long queue at cashier']),
        ('Slow cashier or reception reported', ['mabagal sa reception', 'mabagal ang pagproseso sa reception', 'slow cashier', 'slow reception']),
        ('Unclear queue instructions reported', ['walang malinaw na queue instructions', 'hindi malinaw ang pila', 'unclear queue instructions']),
    ],
    'staff_attitude': [
        ('Negative staff attitude reported', ['masama ugali', 'masama ang ugali', 'masamang ugali']),
        ('Unfair treatment or favoritism reported', ['bias', 'biased', 'favoritism', 'pabor sa kakilala', 'unfair treatment', 'favoring friends']),
        ('Rude or unfriendly behavior reported', ['masungit', 'bastos', 'rude', 'unfriendly', 'disrespectful', 'impolite', 'pasigaw', 'sinungitan']),
    ],
    'consultation_fees': [
        ('High price or fee reported', ['mahal', 'expensive', 'overpriced', 'unaffordable', 'mataas ang presyo', 'price too high']),
        ('Poor value for money reported', ['hindi sulit', 'sayang pera', 'not worth the money']),
    ],
    'medical_certificate': [
        ('Certificate not provided or delayed reported', ['hindi nabigay ang medical certificate', 'hindi nabigay ang med cert', 'delayed cert', 'ayaw magbigay ng med cert', 'walang med cert', 'certificate not provided']),
        ('Unclear certificate requirements reported', ['hindi malinaw ang requirements sa medical certificate', 'unclear certificate requirements']),
    ],
    'doctor_expertise': [
        ('Dissatisfaction with doctor expertise reported', ['hindi magaling ang doctor', 'hindi magaling ang doktor', 'hindi magaling na doctor', 'hindi magaling na doktor', 'not a good doctor']),
        ('Rushed consultation reported', ['minadali ang consultation', 'minadali ang consultasyon', 'rushed consultation', 'consultation was rushed']),
        ('Concern not listened to reported', ['hindi nakinig', 'hindi pinakinggan', 'not listening', 'did not listen', 'didnt listen']),
        ('Wrong diagnosis or prescription reported', ['mali ang diagnosis', 'maling diagnosis', 'misdiagnosed', 'wrong diagnosis', 'maling reseta', 'maling gamot', 'wrong prescription', 'wrong medication']),
    ],
    'doctor_availability': [
        ('Advance notice of doctor absence requested', ['sana sabihin', 'sana ipaalam', 'please notify us', 'please inform us']),
        ('No advance notice reported', ['walang abiso', 'walang announcement', 'hindi sinabi', 'no advance notice']),
        ('Wasted trip reported', ['sayang ang punta', 'sayang ang pagpunta', 'nasayang ang punta']),
        ('Doctor absence or unavailability reported', ['walang doctor', 'walang doktor', 'wala yung doctor', 'wala yung doktor', 'doctor was absent', 'doctor unavailable', 'no doctor']),
    ],
    'appointment_scheduling': [
        ('No available slots reported', ['walang slot', 'fully booked', 'no slots', 'no appointment slots', 'walang available na schedule']),
        ('Double booking reported', ['double booked', 'double booking']),
        ('Appointment cancellation or rescheduling reported', ['na cancel', 'kinansela', 'cancelled', 'canceled', 'reschedule', 'resched']),
        ('Booking or confirmation problem reported', ['hindi na confirm', 'walang confirmation', 'hindi ma book', 'hindi makapag book']),
    ],
    'staff_attentiveness': [
        ('Staff not accommodating or helpful reported', ['not accommodating', 'hindi accommodating', 'not helpful']),
        ('Lack of assistance reported', ['hindi pinansin', 'hindi ako pinansin', 'walang umasikaso', 'walang tumulong', 'ignored', 'no assistance', 'nobody helped', 'left unattended']),
    ],
    'billing_payment': [
        ('Duplicate charge reported', ['double charge', 'duplicate charge', 'charged twice']),
        ('Overcharge or incorrect bill reported', ['overcharged', 'sobra ang singil', 'maling singil', 'wrong bill', 'maling bill', 'incorrect bill', 'billing error']),
        ('Hidden or unexpected charges reported', ['hidden charges', 'hidden fee', 'unexpected charges', 'extra charges']),
        ('No receipt issued reported', ['walang receipt', 'no receipt', 'walang official receipt', 'receipt missing']),
        ('Payment method not accepted reported', ['hindi tinanggap ang card', 'hindi tinanggap ang philhealth', 'hindi tinanggap ang hmo', 'card rejected', 'insurance rejected']),
        ('Discount not given reported', ['walang discount', 'hindi binigyan ng discount', 'hindi binigay ang discount']),
    ],
    'lab_results': [
        ('Delayed results reported', ['matagal ang resulta', 'delayed result', 'delayed results', 'results delayed', 'hindi pa lumalabas ang resulta', 'wala pa ang resulta', 'results took longer than expected', 'results took much longer than expected', 'result took longer than expected', 'results were released later than expected']),
        ('Wrong results reported', ['mali ang resulta', 'wrong result', 'wrong results', 'incorrect results', 'maling laboratory result', 'maling blood test']),
        ('Missing or unreleased results reported', ['nawala ang resulta', 'missing results', 'hindi nabigay ang resulta', 'results not released']),
    ],
    'communication_information': [
        ('Inconsistent or wrong information reported', ['iba iba ang sinabi', 'magkaiba ang sinabi', 'inconsistent information', 'wrong information', 'maling impormasyon']),
        ('Unclear explanation or instructions reported', ['kulang sa paliwanag', 'walang paliwanag', 'hindi ipinaliwanag', 'hindi nagpaliwanag', 'hindi marunong magpaliwanag', 'did not explain', 'didnt explain', 'unclear instructions', 'unclear explanation']),
        ('No reply or update reported', ['walang reply', 'no reply', 'walang update', 'no update', 'hindi sumasagot', 'hindi sinasagot', 'walang sumasagot', 'unresponsive']),
    ],
    'online_system': [
        ('Difficult to use reported', ['booking system was difficult to use', 'website was difficult to use', 'website was hard to use', 'app was difficult to use', 'app was hard to use', 'mahirap gamitin ang website', 'mahirap gamitin ang app', 'hirap gamitin ang website', 'hirap gamitin ang app', 'confusing to use the website']),
        ('Website error reported', ['nag error ang website', 'error ang website', 'website error', 'website down', 'site down']),
        ('System error reported', ['server error', 'system error']),
        ('Submit button not working reported', ['hindi gumagana ang submit', 'hindi gumana ang submit']),
        ('Login problem reported', ['hindi makapag login', 'hindi maka login', 'cannot login', 'cannot log in', 'cant login', 'cant log in', 'unable to login', 'unable to log in']),
        ('App crash or freezing reported', ['app crashed', 'app crashes', 'nag hang', 'hanging']),
    ],
    'cleanliness_hygiene': [
        ('Room not clean on arrival reported', ['room was not clean upon arrival', 'room was not clean on arrival', 'room not clean upon arrival', 'room not clean on arrival', 'hindi malinis ang room pagdating', 'hindi malinis ang consultation room pagdating']),
        ('Restroom cleaning or maintenance needed reported', ['restroom needed cleaning', 'restroom needs cleaning', 'restroom needed maintenance', 'restroom needs maintenance', 'poor restroom cleanliness', 'restroom cleanliness was poor', 'restroom maintenance needed']),
        ('Dirty or smelly restroom reported', ['madumi ang banyo', 'maruming banyo', 'mabaho ang banyo', 'madumi ang comfort room', 'dirty restroom', 'dirty toilet', 'dirty bathroom']),
        ('No gloves or mask reported', ['walang guwantes', 'walang gloves', 'walang mask', 'no gloves', 'no mask']),
        ('Not sterile or disinfected reported', ['hindi sterile', 'not sterile', 'not disinfected', 'hindi nag disinfect']),
        # Crowding and seating already belong to this category in the shared lexicon.
        ('Overcrowded waiting area reported', ['siksikan sa waiting area', 'crowded waiting area', 'cramped waiting area', 'overcrowded waiting area', 'waiting area was overcrowded']),
        ('Insufficient seating reported', ['kulang ang upuan', 'kulang sa upuan', 'walang upuan', 'walang maupuan', 'not enough seats', 'lacked enough seating', 'not enough seating', 'limited seating', 'insufficient seating', 'lack of seating']),
        ('Heat or lack of air conditioning reported', ['mainit', 'walang aircon']),
    ],
    'privacy_confidentiality': [
        ('Private information overheard or exposed reported', ['narinig ng ibang pasyente', 'narinig ng iba', 'nakita ng iba ang record', 'exposed records', 'medical records exposed', 'personal information shared']),
        ('Lack of privacy reported', ['walang privacy', 'no privacy', 'no confidentiality', 'privacy breach']),
    ],
    'accessibility_facility': [
        ('No wheelchair ramp or access reported', ['walang ramp', 'no ramp', 'walang wheelchair access', 'no wheelchair access']),
        ('No or limited parking reported', ['walang parking', 'no parking', 'limited parking', 'kulang sa parking']),
        ('No elevator reported', ['walang elevator', 'no elevator']),
        ('Difficult access or unclear signage reported', ['mahirap hanapin', 'hirap umakyat', 'walang signage', 'poor signage', 'hard to find']),
    ],
    'operating_hours': [
        ('Closed during posted hours reported', ['sarado ang clinic kahit within operating hours', 'sarado kahit clinic hours', 'closed during operating hours', 'closed during clinic hours']),
        ('Posted schedule update requested', ['sana updated ang posted schedule']),
        ('Outdated posted schedule reported', ['hindi updated ang schedule', 'hindi updated ang posted schedule', 'outdated schedule', 'outdated posted schedule']),
        ('Closed early or opened late reported', ['maagang nagsara', 'maaga magsara', 'closed early', 'opened late', 'late magbukas']),
    ],
}

_COMPILED_REASONS = {
    category: [(reason, [_phrase_pattern(phrase) for phrase in phrases]) for reason, phrases in rules]
    for category, rules in REASON_RULES.items()
}


def _phrase_found(normalized, pattern):
    for match in pattern.finditer(normalized):
        # An explicit negative phrase can itself be denied: "not no receipt".
        negated = re.search(rf'\b(?:hindi|not|never|no|walang|wala)(?:\s+{_PARTICLE}){{0,2}}\s*$', normalized[:match.start()])
        if not negated:
            return True
    return False


def _reported_reason(category, evidence):
    normalized = _normalize(evidence)
    if category == 'waiting_time':
        # Require waiting and an explicit causal connector in the same sentence.
        waiting = r'\b(?:naghintay|hintay|waiting|waited|wait|pinaghintay)\b'
        late_doctor = r'\b(?:late (?:dumating (?:ang |si )?)?(?:doktor|doctor)|(?:doktor|doctor) (?:arrived|came) late)\b'
        attribution = waiting + r'.{0,60}\b(?:dahil|kasi|because)\s+(?:(?:ang|si|the)\s+)?' + late_doctor
        if (re.search(attribution, normalized)
                and not re.search(r'\b(?:hindi|not|never)\b', normalized)):
            return 3, 'Patient attributes waiting to late doctor arrival'
    for reason, patterns in _COMPILED_REASONS.get(category, []):
        if any(_phrase_found(normalized, pattern) for pattern in patterns):
            return 2, reason

    if category == 'waiting_time' and re.search(r'\b(?:pila|queue|queues)\b', normalized):
        return 1, 'Wait in queue reported; underlying reason unspecified'
    if category == 'staff_attitude':
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
