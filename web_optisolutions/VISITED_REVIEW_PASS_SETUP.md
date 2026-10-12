# Completed visits and review passes: Admin and Staff

Admin and Staff Scheduled Visits pages have a Visited column. Mark visited confirms attendance for a visit dated today or earlier, records completion, generates a private transaction code, and sends it to the patient's saved email address. A valid email is required. Existing records start as not visited; past dates alone do not imply attendance.

One code is stored per visit, encrypted at rest, with a separate SHA-256 hash for later verification. Retries reuse that code. Completed visits cannot be removed or rescheduled through the Admin page, preserving their doctor/service association. Notes can still be edited. A failed email does not reverse attendance: the page shows Email not sent and offers Retry email.

This is the Admin/Staff stage only. Patient code redemption, enforcing one review per visit, and review distribution by doctor/service are not implemented yet. The `review_pass_used_at` column is reserved for that subsequent step. Existing public review submission is not gated by these codes yet.

## Apply on an existing database

Back up the database before applying schema changes. Run this single additive migration from the Laravel folder, rather than running the old pending baseline migrations against the imported SQL database:

```bash
php artisan migrate --path=database/migrations/2026_10_12_000001_add_visited_and_review_pass_to_schedule_visit.php --force
php artisan view:clear
```

Do not run `migrate:fresh`. This migration adds fields to `schedule_visit` without replacing its records.

Configure working SMTP in `.env`; delivery uses the existing Laravel mail configuration, with no queue worker requirement. SMTP success means the server accepted the email, not confirmation it reached the inbox. Keep `APP_KEY` unchanged after issuing passes, since it encrypts their codes. After mail configuration changes, run `php artisan config:clear`.

Test an attended visit with a controlled patient email, confirm the correct doctor/service and code in the email, and verify that repeating Mark visited does not mint a second code. No real emails are sent by the automated tests.
