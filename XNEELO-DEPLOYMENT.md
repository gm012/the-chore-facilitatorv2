# xneelo deployment

No deployment or live inbox verification is implied by local tests. Use an access-restricted staging host with synthetic data first. The GitHub Pages review is a static preview and deliberately does not submit forms or expose the admin backend.

## Hosting and private configuration

1. Confirm PHP 8.2+ and PDO MySQL, mbstring and fileinfo with the hosting panel. Use `upload_max_filesize=8M`, `post_max_size=10M`, display_errors Off, log_errors On. Do not leave a public phpinfo page. Match the CLI PHP version to the website version.
2. Confirm the account's real web root. xneelo may resolve `public_html` to `/usr/www/users/ACCOUNT`; do not assume its parent is writable/private. Arrange private storage under the actual `/usr/home/ACCOUNT` account directory outside **all** public domain roots, with access limited to the account/PHP user.
3. Create private `tcf-storage` (0700) and copy `server-config.example.php` to a private `config.php` (0600). Populate real database details and paths. Generate a key using `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Keep that value private and stable. The example key must be replaced. Keep `mail_enabled` false until the mailbox and transport are ready.
4. Set `TCF_CONFIG_FILE` to the absolute private config path in the server environment. For Apache/mod_php, `SetEnv TCF_CONFIG_FILE /PRIVATE/config.php` in the deployed `.htaccess` can supply the **path**, never secrets. Confirm xneelo's PHP handler passes it through; otherwise ask the host to set the environment for PHP. CLI/cron must also export TCF_CONFIG_FILE. Do not fall back to putting credentials inside the public root.
5. Create/import the database per DATABASE-SETUP.md, then create the initial staff account per ADMIN-SETUP.md. Set `environment` to `production`, `base_url` to `https://thechorefacilitator.co.za`, and `enabled` to true only when configuration is complete.

## Upload

Upload the public HTML/CSS/JS, images, forms.php, committed configuration loader, form-schema.json, admin/, api/ and Apache `.htaccess` files. The server reads form-schema.json even though Apache blocks public downloads. Upload cron tools only if you will use them, retaining `tools/.htaccess`. Import SQL privately; the database directory does not need to be deployed. Do **not** upload `.git`, `.github`, `_preview`, local test files, backup archives, example config, build-preview.cjs or development docs. Keep the original production HTML, not the `_preview` output, which contains review-only form controls/noindex.

The root `.htaccess` redirects the confirmed production hosts to canonical HTTPS, sets public-site security headers and blocks configuration/schema/development files. Subdirectory rules block direct includes/tools access and set admin headers. Verify AllowOverride, rewrite and headers support. PHP independently requires HTTPS outside local mode. Do not blindly trust forwarded headers or enable local mode on hosting to bypass HTTPS errors; resolve the server's HTTPS setting with the host.

## Email and cron

Confirm `admin@thechorefacilitator.co.za` is an active mailbox and the domain has the appropriate SPF/DKIM/DMARC configuration for the host's mail transport. PHP mail() is used; no SMTP credentials are exposed. Enable private `mail_enabled` after configuration.

Example cron commands (replace paths and PHP executable with the account's values):

```cron
*/5 * * * * TCF_CONFIG_FILE=/PRIVATE/config.php /PATH/TO/php /WEB/ROOT/tools/send-notifications.php >> /PRIVATE/notification-worker.log 2>&1
15 2 * * * TCF_CONFIG_FILE=/PRIVATE/config.php /PATH/TO/php /WEB/ROOT/tools/maintenance.php >> /PRIVATE/maintenance.log 2>&1
```

The worker sends up to 25 queued notices per run, retries failed transport attempts up to five times, and allows staff to requeue failures from Settings. Rotate/restrict worker logs. Password resets are sent immediately and require working email; notification cron does not send reset tokens.

## Before launch

- Verify HTTPS, canonical redirects, public links, 404 page, security headers and no PHP warnings.
- Confirm unauthenticated admin/document access fails; direct config, includes, database and tool URLs are forbidden. Test an actual private file URL cannot resolve anywhere public.
- Submit all three forms with agreed synthetic data; see the correct records in staff screens. Test approve → candidate → consideration → confirmed placement, both placement types, notes and status history.
- Verify real notification/reset inbox delivery separately, including spam folders and reset single use. A public form may succeed even if the notification fails: staff must check the dashboard.
- Test regular vs Super Admin access, disable an account and confirm its session stops working. Check expired sessions and CSRF failures.
- Verify mobile/keyboard behavior, private upload/download and invalid-file rejection.
- Confirm production has no review `noindex` or `data-site-mode="preview"`, and sitemap/canonicals use the final domain. Admin/API must remain noindex. Search Console verification/submission is a separate owner action.
- Obtain the client's approval of privacy wording, staff access, lawful document collection and retention; no legal compliance certification is implied by this code.
- Perform and verify a recoverable database + document + configuration backup. Remove the initial-admin setup tool. Keep a deployment rollback copy.

Only after these checks should the operational system be called live-ready.
