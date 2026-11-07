# Private chat attachments

New patient chat uploads use the `chat_attachments` disk in
`storage/app/private/chat-attachments`. Do not create a public symlink to this folder.
Database attachment paths stay unchanged; views generate a permission-checked
`/chatbot/attachments/{reply}` URL instead of `/storage/chat-attachments/...`.

Active Admin/Staff accounts can open these URLs after signing in. The patient
browser that uploaded a file can open that upload during its Laravel session.
Knowing a URL or supplying a conversation ID does not grant permission.
Older patient uploads remain available to authorized Admin/Staff after migration.

## Deployment

1. Back up the current uploads and deploy the code.
2. Block the old public URLs before moving files. Apache uses the rule in
   `public/.htaccess` (requires enabled overrides). For a separately managed Nginx
   virtual host, add these locations to its `server` block, validate configuration,
   and reload Nginx. The Docker Nginx configuration already includes them:

   ```nginx
   location = /storage/chat-attachments { return 404; }
   location ^~ /storage/chat-attachments/ { return 404; }
   ```

3. Refresh Laravel configuration and routes, then migrate only these files:

   ```sh
   php artisan config:clear
   php artisan route:clear
   php artisan chat:privatize-attachments --dry-run
   php artisan chat:privatize-attachments
   ```

The command verifies matching SHA-256 checksums before removing a public copy.
It is safe to rerun. Conflicting private copies and unexpected paths are left
unchanged, reported, and cause a nonzero exit code. Check the output before
considering the migration complete.

4. Confirm that an old `/storage/chat-attachments/...` URL returns 404; an
   unrelated guest gets 403 from the new URL; and authorized staff can still
   open the attachment. New images display inline; PDFs download. Responses are
   marked private and non-cacheable. Profiles and other public images are unaffected.

This change does not require a database migration or a Flutter code change.
