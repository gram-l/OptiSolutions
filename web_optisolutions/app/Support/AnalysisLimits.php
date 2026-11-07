<?php

namespace App\Support;

// Keep aligned with sentiment_service/sentiment_api/limits.py.
class AnalysisLimits
{
    public const MAX_TEXT_LENGTH = 5000;
    public const MAX_BATCH_ITEMS = 50;
    public const MAX_REQUEST_BYTES = 262144;
    public const MAX_ID_LENGTH = 100;
}
