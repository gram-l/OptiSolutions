import json
import joblib
from pathlib import Path
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt

# Load the trained pipeline once at startup, not per-request
MODEL_PATH = Path(__file__).resolve().parent.parent / "ml_training" / "sentiment_model.pkl"
model = joblib.load(MODEL_PATH)

@csrf_exempt
def predict_sentiment(request):
    if request.method != "POST":
        return JsonResponse({"error": "POST request required"}, status=405)

    try:
        body = json.loads(request.body)
        text = body.get("feedback_text", "").strip()
    except json.JSONDecodeError:
        return JsonResponse({"error": "Invalid JSON"}, status=400)

    if not text:
        return JsonResponse({"error": "feedback_text is required"}, status=400)

    label = model.predict([text])[0]
    proba = model.predict_proba([text])[0]
    confidence = float(max(proba))

    label_map = {"positive": "Positive", "neutral": "Neutral", "negative": "Negative"}

    return JsonResponse({
        "sentiment_label": label_map[label],
        "confidence_score": round(confidence, 3)
    })

    #Root cause / diagnostic analytrics for negative sentiment and complaints

ISSUE_CATEGORIES = {
    "waiting_time": [
        "tagal", "matagal", "haba ng pila", "pila", "wait", "waiting",
        "hintay", "naghintay", "ang bagal", "late", "delay", "queue"
    ],
    "staff_attitude": [
        "bastos", "sungit", "rude", "unfriendly", "walang modo",
        "pabebe", "arte", "mataray", "receptionist", "hindi magalang"
    ],
    "consultation_fees": [
        "mahal", "presyo", "bayad", "fee", "singil", "sobrang mahal",
        "expensive", "sayang pera", "hindi sulit", "consultation fee"
    ],
    "medical_certificate": [
        "med cert", "medical certificate", "certificate", "sertipiko",
        "requirements", "documents", "hindi nabigay", "delayed cert"
    ],
    "doctor_expertise": [
        "hindi propesyonal", "unprofessional",
        "mali ang diagnosis", "misdiagnosed", "wrong diagnosis",
        "rushed", "nagmamadali", "hindi nakinig", "not listening",
        "walang pakialam", "careless", "incompetent", "walang alam", "walang karanasan", 
        "walang kaalaman", "walang experience", "walang training"
    ]
}

@csrf_exempt
def diagnose_root_causes(request):
    if request.method != "POST":
        return JsonResponse({"error": "POST request required"}, status=405)

    try:
        body = json.loads(request.body)
        items = body.get("items", [])  # list of {"id": ..., "text": ..., "source": "feedback" | "complaint"}
    except json.JSONDecodeError:
        return JsonResponse({"error": "Invalid JSON"}, status=400)

    # Separate counters per source
    feedback_counts = {cat: 0 for cat in ISSUE_CATEGORIES}
    complaint_counts = {cat: 0 for cat in ISSUE_CATEGORIES}
    matched_details = []

    for entry in items:
        text = entry.get("text", "").lower()
        source = entry.get("source")
        matched_categories = []

        for category, keywords in ISSUE_CATEGORIES.items():
            if any(kw in text for kw in keywords):
                matched_categories.append(category)
                if source == "feedback":
                    feedback_counts[category] += 1
                elif source == "complaint":
                    complaint_counts[category] += 1

        matched_details.append({
            "id": entry.get("id"),
            "source": source,
            "matched_categories": matched_categories
        })

    feedback_ranked = dict(sorted(feedback_counts.items(), key=lambda x: x[1], reverse=True))
    complaint_ranked = dict(sorted(complaint_counts.items(), key=lambda x: x[1], reverse=True))

    return JsonResponse({
        "feedback_category_counts": feedback_ranked,
        "complaint_category_counts": complaint_ranked,
        "details": matched_details
    })

@csrf_exempt
def categorize_complaint(request):
    if request.method != "POST":
        return JsonResponse({"error": "POST request required"}, status=405)

    try:
        body = json.loads(request.body)
        text = body.get("complaint_text", "").lower().strip()
    except json.JSONDecodeError:
        return JsonResponse({"error": "Invalid JSON"}, status=400)

    if not text:
        return JsonResponse({"error": "complaint_text is required"}, status=400)

    for category, keywords in ISSUE_CATEGORIES.items():
        if any(kw in text for kw in keywords):
            return JsonResponse({"category": category})

    return JsonResponse({"category": "other"})