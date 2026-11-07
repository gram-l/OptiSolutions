# Analysis request limits

Existing features, models, category rules, and analysis triggers are unchanged.
Reviews and chatbot complaints continue their existing per-submission analysis;
dashboard and feedback-page diagnostics still analyze the records selected by
their existing filters. No Flutter code or API response format is changed.

The matching constants live in `sentiment_api/limits.py` and Laravel's
`app/Support/AnalysisLimits.php`:

| Limit | Value |
| --- | --- |
| Text per review/complaint | 5,000 Unicode characters |
| Records per diagnostic request | 50 |
| Encoded JSON request body | 256 KiB (262,144 bytes) |
| Diagnostic record ID | 100 characters |

Laravel splits diagnostics on both item count and actual UTF-8 JSON byte size.
It merges complete per-record findings and category counts across batches.
A failed or incomplete batch makes the analysis unavailable; partial counts
are not presented as complete results. Text is never truncated.

New submissions over the text limit receive validation errors. Historical text
over the limit stays unchanged: sentiment backfill leaves it pending and
diagnostics report unavailable instead of silently skipping it. Existing 5-second
HTTP analysis timeouts and the backfill command's 30-second timeout are preserved.

Deploy Laravel batching first, then deploy Django limits. Restart the Django
service to load the new constants. There is no database migration or model
retraining step. Deploy both sides with matching limits.

These changes bound request size and batch work; they do not introduce service
authentication, rate/concurrency limits, or a worker processing deadline.
