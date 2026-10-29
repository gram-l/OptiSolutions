import json
import joblib
from pathlib import Path
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from .issue_categories import ISSUE_CATEGORIES, category_matches, classify_issue
from .text_preprocessing import model_inputs
from .issue_evidence import issue_findings

# Load the trained pipeline once at startup, not per-request
MODEL_PATH = Path(__file__).resolve().parent.parent / "ml_training" / "sentiment_model.pkl"
model = joblib.load(MODEL_PATH)

@csrf_exempt
def predict_sentiment(request):
    if request.method != "POST":
        return JsonResponse({"error": "POST request required"}, status=405)

    try:
        body = json.loads(request.body)
        text = body.get("feedback_text") if isinstance(body, dict) else None
        if not isinstance(text, str):
            return JsonResponse({"error": "feedback_text must be a string"}, status=400)
        text = text.strip()
    except json.JSONDecodeError:
        return JsonResponse({"error": "Invalid JSON"}, status=400)

    if not text:
        return JsonResponse({"error": "feedback_text is required"}, status=400)

    inputs = model_inputs(model, [text])
    if not inputs[0].strip():
        return JsonResponse({"error": "feedback_text must contain review text"}, status=400)
    label = model.predict(inputs)[0]
    proba = model.predict_proba(inputs)[0]
    confidence = float(max(proba))

    label_map = {"positive": "Positive", "neutral": "Neutral", "negative": "Negative"}

    return JsonResponse({
        "sentiment_label": label_map[label],
        "confidence_score": round(confidence, 3)
    })

@csrf_exempt
def diagnose_root_causes(request):
    if request.method != "POST":
        return JsonResponse({"error": "POST request required"}, status=405)

    try:
        body = json.loads(request.body)
    except (json.JSONDecodeError, UnicodeDecodeError):
        return JsonResponse({"error": "Invalid JSON"}, status=400)

    if not isinstance(body, dict) or not isinstance(body.get("items"), list):
        return JsonResponse({"error": "items must be a list"}, status=400)
    items = body['items']
    identities = set()
    for entry in items:
        if (not isinstance(entry, dict)
                or type(entry.get('id')) not in (int, str)
                or not str(entry['id']).strip()
                or not isinstance(entry.get('text'), str)
                or entry.get('source') not in ('feedback', 'complaint')):
            return JsonResponse({"error": "Each item requires an id, text string, and feedback or complaint source"}, status=400)
        identity = (entry['source'], str(entry['id']))
        if identity in identities:
            return JsonResponse({"error": "Duplicate record within a source"}, status=400)
        identities.add(identity)

    # Separate counters per source
    feedback_counts = {cat: 0 for cat in ISSUE_CATEGORIES}
    complaint_counts = {cat: 0 for cat in ISSUE_CATEGORIES}
    matched_details = []

    for entry in items:
        text = entry.get("text") or ""
        source = entry.get("source")
        matched_categories = []

        for category in category_matches(text):
            matched_categories.append(category)
            if source == "feedback":
                feedback_counts[category] += 1
            elif source == "complaint":
                complaint_counts[category] += 1

        matched_details.append({
            "id": entry.get("id"),
            "source": source,
            "matched_categories": matched_categories,
            "findings": issue_findings(text),
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
        text = (body.get("complaint_text") or "").strip()
    except json.JSONDecodeError:
        return JsonResponse({"error": "Invalid JSON"}, status=400)

    if not text:
        return JsonResponse({"error": "complaint_text is required"}, status=400)

    return JsonResponse({"category": classify_issue(text)})
