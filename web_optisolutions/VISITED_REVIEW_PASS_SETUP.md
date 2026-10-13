# Completed visits and review passes: Admin and Staff

Admin and Staff Scheduled Visits pages have a Visited column. Mark visited confirms attendance for a visit dated today or earlier, records completion, generates a private transaction code, and sends it to the patient's saved email address. A valid email is required. Existing records start as not visited; past dates alone do not imply attendance.

One code is stored per visit, encrypted at rest, with a separate SHA-256 hash for later verification. Retries reuse that code. Completed visits cannot be removed or rescheduled through the Admin page, preserving their doctor/service association. Notes can still be edited. A failed email does not reverse attendance: the page shows Email not sent and offers Retry email.

The patient chatbot requires the emailed transaction code before rating. The chatbot and review submission endpoints enforce one review per completed visit. Codes are consumed only when feedback is saved; abandoned verification does not consume a pass. Reviews store `visit_id`, linking them to the visit's patient, doctor, and service. Review distribution charts are a separate next step.

Apply the review-link migration after the visited/pass migration:

```bash
php artisan migrate --path=database/migrations/2026_10_13_000001_link_feedback_to_completed_visit.php --force
php artisan view:clear
```

The review API requires `transaction_code` alongside `star_rating` and optional `feedback_text`. Submitted patient IDs cannot override the patient associated with the code. Historical feedback remains intact with a null `visit_id`.

New passes contain 20 uppercase letters/digits. Existing 32-character passes remain valid and email retries preserve the original code. Each email includes a privately generated PNG QR code and a clickable review link. Both open `/home`, start the chatbot's Submit Review/Rating flow, and enter the code automatically. The patient still chooses the rating and comment. The code is placed in a URL fragment so it is not sent in HTTP requests, and the chatbot removes it from the visible URL.

Set `APP_URL` in the VPS `.env` to the public website address (including `https://` when configured), then run `php artisan config:clear`. Install the QR dependency with `composer install --no-dev --optimize-autoloader` after deploying the updated Composer files, and run `php artisan view:clear`. QR images require no external QR service or GD/Imagick extension. Already delivered emails retain their original contents.

## Apply on an existing database

Back up the database before applying schema changes. Run this single additive migration from the Laravel folder, rather than running the old pending baseline migrations against the imported SQL database:

```bash
php artisan migrate --path=database/migrations/2026_10_12_000001_add_visited_and_review_pass_to_schedule_visit.php --force
php artisan view:clear
```

Do not run `migrate:fresh`. This migration adds fields to `schedule_visit` without replacing its records.

Configure working SMTP in `.env`; delivery uses the existing Laravel mail configuration, with no queue worker requirement. SMTP success means the server accepted the email, not confirmation it reached the inbox. Keep `APP_KEY` unchanged after issuing passes, since it encrypts their codes. After mail configuration changes, run `php artisan config:clear`.

Test an attended visit with a controlled patient email, confirm the correct doctor/service and code in the email, and verify that repeating Mark visited does not mint a second code. No real emails are sent by the automated tests.
