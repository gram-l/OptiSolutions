from django.urls import path
from . import views

urlpatterns = [
    path("api/predict/", views.predict_sentiment, name="predict_sentiment"),
    path("api/diagnose/", views.diagnose_root_causes, name="diagnose_root_causes"),
]