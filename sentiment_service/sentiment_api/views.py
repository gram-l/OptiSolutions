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