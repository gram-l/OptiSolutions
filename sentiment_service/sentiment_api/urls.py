from django.urls import path
from . import views

urlpatterns = [
    path("api/predict/", views.predict_sentiment, name="predict_sentiment"),
]