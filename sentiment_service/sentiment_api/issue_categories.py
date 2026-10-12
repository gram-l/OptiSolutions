"""Shared keyword categories for complaints and diagnostic analytics."""

import re
import unicodedata

ISSUE_CATEGORIES = {
    "waiting_time": [
        "tagal", "matagal", "haba ng pila", "pila", "wait", "waiting",
        "hintay", "naghintay", "ang bagal", "mabagal", "babagal", "wait time", "late", "delay", "queue",
    ],
    "staff_attitude": [
        "bastos", "sungit", "rude", "unfriendly", "walang modo",
        "pabebe", "arte", "mataray", "hindi magalang", "bias", "biased", "favoritism", "pabor sa kakilala",
    ],
    "consultation_fees": [
        "mahal", "presyo", "bayad", "fee", "singil", "sobrang mahal",
        "expensive", "sayang pera", "hindi sulit", "consultation fee",
    ],
    "medical_certificate": [
        "med cert", "medical certificate", "certificate", "sertipiko",
        "requirements", "documents", "hindi nabigay", "delayed cert",
    ],
    "doctor_expertise": [
        "hindi propesyonal", "unprofessional", "mali ang diagnosis",
        "misdiagnosed", "wrong diagnosis", "rushed", "nagmamadali",
        "hindi nakinig", "not listening", "walang pakialam", "careless",
        "incompetent", "walang alam", "walang karanasan", "walang kaalaman",
        "walang experience", "walang training",
    ],
    "doctor_availability": [
        "walang doktor", "walang doctor", "wala ang doktor", "wala ang doctor",
        "wala yung doktor", "wala yung doctor", "no doctor", "doctor was absent",
        "hindi pumasok ang doktor", "hindi dumating ang doktor", "hindi dumating ang doctor",
        "walang specialist", "walang available na doktor", "hindi available ang doktor",
        "doctor is not available", "doctor not available",
    ],
    "appointment_scheduling": [
        "walang slot", "fully booked", "walang available na schedule", "walang schedule",
        "na-cancel", "kinansela", "cancelled", "canceled", "reschedule", "resched",
        "walang appointment", "nawala ang appointment", "hindi nabigyan ng appointment",
        "mahirap magpa-appointment", "hirap magpa-book", "walk-in lang", "double booked",
        "hindi na-confirm", "walang confirmation", "scheduling", "scheduling problem", "schedule problem",
    ],
    "staff_attentiveness": [
        "hindi pinansin", "ignored", "walang umasikaso", "hindi inasikaso",
        "napabayaan", "neglected", "inattentive", "hindi tumulong", "walang tumulong",
        "unhelpful", "hindi matulungin", "walang nag-assist", "hindi inalagaan",
        "walang nag-entertain", "hindi ako pinansin",
    ],
    "billing_payment": [
        "overcharged", "sobra ang singil", "maling singil", "hidden charges",
        "hidden fee", "walang resibo", "no receipt", "walang official receipt",
        "refund", "hindi nirefund", "double charge", "dalawang beses na-charge",
        "mali ang sukli", "walang sukli", "cash only", "hindi tinanggap ang card",
        "hindi tinanggap ang philhealth", "philhealth", "hmo", "insurance",
        "walang discount", "hindi binigyan ng discount", "senior discount", "pwd discount",
    ],
    "lab_results": [
        "resulta", "lab result", "laboratory", "hindi pa lumalabas ang resulta",
        "matagal ang resulta", "delayed result", "wrong result", "mali ang resulta",
        "naipalit", "x-ray", "xray", "ultrasound", "hindi nabigay ang resulta",
        "walang resulta", "result ko", "blood test", "ecg",
    ],
    "communication_information": [
        "hindi ipinaliwanag", "walang paliwanag", "hindi malinaw", "unclear",
        "walang nagsabi", "hindi sumasagot", "di sumasagot", "hindi sinasagot",
        "no reply", "walang reply", "hindi nag-reply", "unresponsive", "walang update",
        "no update", "walang information", "confusing", "hindi alam ng staff",
        "iba-iba ang sinabi", "walang sumagot", "hindi nasagot ang tanong", "hindi marunong magpaliwanag", "hindi nagpaliwanag",
    ],
    "online_system": [
        "chatbot", "website", "online booking", "online appointment", "mobile app",
        "hindi mabuksan", "nag-error", "error", "nag-hang", "nag hang", "hanging",
        "hindi gumagana", "not working", "hindi makapag-login", "can't login",
        "cannot login", "login", "slow loading", "ang bagal ng website",
    ],
    "cleanliness_hygiene": [
        "madumi", "marumi", "mabaho", "dirty", "unsanitary", "smelly",
        "kalat", "mainit", "walang aircon", "siksikan", "crowded", "cramped",
        "comfort room", "restroom", "banyo", "palikuran", "walang upuan",
        "kulang ang upuan", "hindi sterile", "not sterile", "hindi malinis",
        "walang guwantes", "walang gloves", "walang mask", "contaminated",
        "hindi nag-disinfect", "reused", "nahawa", "infection",
    ],
    "privacy_confidentiality": [
        "walang privacy", "privacy", "confidential", "confidentiality",
        "narinig ng iba", "narinig ng ibang pasyente", "ibinunyag", "chismis",
        "pinag-usapan ang sakit ko", "data privacy", "nakita ng iba ang record",
    ],
    "accessibility_facility": [
        "parking", "walang parking", "malayo", "mahirap hanapin", "hagdan",
        "walang elevator", "walang ramp", "wheelchair", "walang priority lane",
        "priority lane", "walang pwd", "hirap umakyat", "walang signage",
    ],
    "operating_hours": [
        "sarado", "naka-close", "nagsara", "maagang nagsara", "maaga magsara",
        "operating hours", "hindi nag-open", "hindi bukas", "closed na",
        "lunch break", "walang tao", "walang nagbukas",
    ],
}

# Specific issue phrases rather than generic words such as "staff" or "doctor".
_ADDITIONAL_PHRASES = {
    'waiting_time': ['antagal', 'angtagal', 'sobrang bagal', 'mabagal na serbisyo', 'slow service', 'slow staff', 'long wait', 'long queues', 'took too long', 'waited for hours', 'kept waiting', 'inaabot ng oras', 'oras sa pila', 'ang haba ng pila', 'napakatagal', 'pinaghintay'],
    'staff_attitude': ['masungit', 'suplada', 'suplado', 'sinungitan', 'binastos', 'pinagalitan', 'pasigaw', 'disrespectful', 'impolite', 'arrogant', 'sarcastic', 'bad attitude', 'unwelcoming', 'favoring friends', 'unfair treatment', 'namamahiya', 'nangmamaliit'],
    'consultation_fees': ['costly', 'overpriced', 'high consultation fee', 'too expensive', 'unaffordable', 'price too high', 'napakamahal', 'kamahalan', 'mahal na consultation', 'hindi abot kaya', 'mataas ang presyo'],
    'medical_certificate': ['medcert', 'medical cert', 'med certificate', 'medical clearance', 'fit to work', 'sick leave certificate', 'medical documents', 'maling certificate', 'kulang ang certificate', 'ayaw magbigay ng med cert'],
    'doctor_expertise': ['wrong medication', 'wrong prescription', 'incorrect diagnosis', 'misdiagnosis', 'wrong treatment', 'rushed consultation', 'did not examine', 'didnt examine', 'did not listen', 'didnt listen', 'hindi sinuri', 'hindi ineksamin', 'maling gamot', 'maling reseta', 'maling diagnosis', 'minadali', 'hindi nakinig ang doktor'],
    'doctor_availability': ['absent doctor', 'doctor unavailable', 'doctor is unavailable', 'doctor not present', 'doctor did not arrive', 'doctor didnt arrive', 'no specialist', 'doktor wala', 'wala si doktor', 'hindi pumasok ang doctor', 'walang dumating na doktor', 'umalis ang doktor'],
    'appointment_scheduling': ['booking problem', 'booking issue', 'appointment cancelled', 'appointment canceled', 'lost booking', 'no slots', 'no appointment slots', 'schedule conflict', 'wrong schedule', 'double booking', 'appointment not confirmed', 'hindi ma book', 'hindi makapag book', 'hindi makapagpa appointment', 'maling schedule', 'nagkagulo ang schedule'],
    'staff_attentiveness': ['no assistance', 'no one helped', 'nobody helped', 'not attended', 'did not assist', 'didnt assist', 'did not help', 'didnt help', 'not paying attention', 'left unattended', 'walang tumutulong', 'walang pumapansin', 'di pinapansin', 'hindi pinapansin', 'walang nag aasikaso', 'walang nag assist', 'kulang sa asikaso'],
    'billing_payment': ['incorrect bill', 'wrong bill', 'billing error', 'payment failed', 'payment issue', 'charged twice', 'duplicate charge', 'unexpected charges', 'extra charges', 'receipt missing', 'no receipt issued', 'insurance rejected', 'card rejected', 'maling bill', 'maling bayad', 'kulang ang sukli', 'hindi tinanggap ang hmo', 'hindi binigay ang discount'],
    'lab_results': ['test results', 'blood test result', 'results delayed', 'missing results', 'incorrect results', 'results not released', 'lost specimen', 'wrong specimen', 'lab report', 'maling laboratory result', 'hindi lumabas ang resulta', 'wala pa ang resulta', 'nawala ang resulta', 'maling blood test'],
    'communication_information': ['poor explanation', 'did not explain', 'didnt explain', 'no explanation', 'unclear instructions', 'wrong information', 'misinformation', 'no response', 'unanswered questions', 'not informed', 'lack of updates', 'maling impormasyon', 'kulang sa paliwanag', 'kulang sa impormasyon', 'hindi sinabi', 'hindi sinabihan', 'hindi inexplain', 'walang sumasagot', 'walang nag update'],
    'online_system': ['site down', 'website down', 'app crashed', 'app crashes', 'server error', 'system error', 'technical issue', 'cannot log in', 'cant log in', 'unable to login', 'unable to log in', 'page wont load', 'broken link', 'bot not responding', 'chatbot walang sagot', 'ayaw gumana ang app', 'ayaw mag load', 'hindi maka login'],
    'cleanliness_hygiene': ['unclean', 'filthy', 'poor hygiene', 'dirty toilet', 'dirty restroom', 'bad smell', 'no sanitation', 'not disinfected', 'no gloves', 'no mask', 'amoy', 'hindi nililinis', 'maruming banyo', 'maduming clinic', 'walang linis', 'kulang sa linis'],
    'privacy_confidentiality': ['privacy breach', 'exposed records', 'leaked information', 'personal information shared', 'no confidentiality', 'no privacy', 'overheard diagnosis', 'medical records exposed', 'sinabi sa iba', 'pinakita ang record', 'nilabas ang personal information'],
    'accessibility_facility': ['no parking', 'limited parking', 'no elevator', 'no wheelchair access', 'no ramp', 'hard to find', 'poor signage', 'broken equipment', 'kulang sa parking', 'walang wheelchair access', 'sirang elevator', 'sirang kagamitan', 'walang direksyon'],
    'operating_hours': ['closed early', 'opened late', 'clinic closed', 'closed clinic', 'hours incorrect', 'incorrect opening hours', 'not open', 'closed during operating hours', 'late magbukas', 'hindi pa bukas', 'maagang sarado', 'sarado kahit clinic hours'],
}
for _category, _phrases in _ADDITIONAL_PHRASES.items():
    ISSUE_CATEGORIES[_category].extend(_phrases)


def _normalize(text):
    text = unicodedata.normalize('NFKC', text or '').casefold()
    text = re.sub(r"[’'`]", '', text)
    text = re.sub(r'[^\w\s]', ' ', text)
    aliases = {'di': 'hindi', 'hndi': 'hindi', 'd': 'hindi', 'doc': 'doktor', 'dok': 'doktor',
               'doktora': 'doktor', 'sched': 'schedule', 'mabgal': 'mabagal', 'mabagl': 'mabagal',
               'matgal': 'matagal', 'masungt': 'masungit', 'resibo': 'receipt'}
    return ' '.join(aliases.get(token, token) for token in text.split())


_PARTICLE = r'(?:po|ho|naman|talaga|very|so|the)'


def _phrase_pattern(phrase):
    words = _normalize(phrase).split()
    gap = rf'(?:\s+{_PARTICLE}){{0,2}}\s+'
    return re.compile(r'(?<!\w)' + gap.join(re.escape(word) for word in words) + r'(?!\w)')


_LEXICON = {category: [(phrase, _phrase_pattern(phrase)) for phrase in phrases]
            for category, phrases in ISSUE_CATEGORIES.items()}


def category_matches(text):
    """Match whole words; rank specific phrases before generic keywords."""
    text = _normalize(text)
    scores = {}
    for category, lexicon in _LEXICON.items():
        matched = []
        for phrase, pattern in lexicon:
            for match in pattern.finditer(text):
                # "Not rude" does not report rudeness; explicitly negative
                # phrases such as "no doctor" and "hindi pinansin" still match.
                starts_negative = _normalize(phrase).split()[0] in {'hindi', 'not', 'no', 'walang', 'wala', 'never'}
                negated = re.search(rf'\b(?:hindi|not|never|no)(?:\s+{_PARTICLE}){{0,2}}\s*$', text[:match.start()])
                if starts_negative or not negated:
                    matched.append(phrase)
                    break
        if matched:
            scores[category] = (max(len(kw.split()) for kw in matched), max(len(kw) for kw in matched))
    return sorted(scores, key=lambda category: scores[category], reverse=True)


def classify_issue(text):
    matches = category_matches(text)
    return matches[0] if matches else "other"
