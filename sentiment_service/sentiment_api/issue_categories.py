"""Shared keyword categories for complaints and diagnostic analytics."""

import re

ISSUE_CATEGORIES = {
    "waiting_time": [
        "tagal", "matagal", "haba ng pila", "pila", "wait", "waiting",
        "hintay", "naghintay", "ang bagal", "late", "delay", "queue",
    ],
    "staff_attitude": [
        "bastos", "sungit", "rude", "unfriendly", "walang modo",
        "pabebe", "arte", "mataray", "receptionist", "hindi magalang",
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
        "hindi na-confirm", "walang confirmation",
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
        "iba-iba ang sinabi", "walang sumagot", "hindi nasagot ang tanong",
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


def category_matches(text):
    """Match whole words; rank specific phrases before generic keywords."""
    text = " ".join((text or "").casefold().replace("’", "'").split())
    scores = {}
    for category, keywords in ISSUE_CATEGORIES.items():
        matched = [kw for kw in keywords if re.search(r"(?<!\w)" + re.escape(kw) + r"(?!\w)", text)]
        if matched:
            scores[category] = (max(len(kw.split()) for kw in matched), max(len(kw) for kw in matched))
    return sorted(scores, key=lambda category: scores[category], reverse=True)


def classify_issue(text):
    matches = category_matches(text)
    return matches[0] if matches else "other"
