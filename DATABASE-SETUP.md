# Database setup and recovery

1. Create an empty database and dedicated database user in the hosting panel. Obtain the actual database host from xneelo; do not assume localhost.
2. Import `database/schema.sql` once using the panel's SQL import or a database administration connection. It creates InnoDB tables, utf8mb4 indexes/relationships and initial settings. It contains no accounts, passwords or sample personal data. Do not reimport over an operational database.
3. The runtime user requires SELECT, INSERT, UPDATE and DELETE on this database only. Import/schema maintenance requires separate CREATE/ALTER/INDEX privileges. Do not run the website using a database root account. If the panel cannot separate these roles, document and restrict the host-managed user to this one database.
4. Store host, port, name, user and password in private PHP configuration or server environment, never HTML/JavaScript or a committed config file.

The core relationships are clients → requests → considerations/placements ← candidates. Applications create candidates only after approval. Notes, document metadata and history reference their source records; actual files live in private account storage. Public retries use session-bound idempotency receipts to prevent duplicate saves from the same form/session. Separate submissions are not merged by email because unauthenticated visitors must not overwrite other households.

## Back up and restore

Use a restricted database client option file outside the web root instead of a password on the command line. Back up daily and before changes:

```bash
mysqldump --defaults-extra-file=/PRIVATE/db-backup.cnf --single-transaction --quick --hex-blob DATABASE_NAME > /PRIVATE/BACKUPS/tcf-database.sql
```

Use `mariadb-dump` where provided instead. Encrypt and securely copy the database export, private documents and private configuration off the server. Limit backup access to authorised maintainers, set an agreed retention period and keep exports outside the web root. A copy on the same hosting account is not sufficient protection.

To restore, pause public intake/staff updates, import into an empty database, restore matching private document files and configuration, correct private filesystem permissions, then verify login, record counts, relationships and document downloads. Restore database and files from a consistent checkpoint. For a fully consistent backup, briefly pause document uploads while the database and document snapshot are captured. Test restoration on an isolated, access-restricted environment before relying on backups. Never restore real personal data onto GitHub Pages or an unrestricted review site.

## Retention and maintenance

Run `tools/maintenance.php` daily to remove expired reset records, old submission receipts and stale request-rate counters. It does not erase business records or documents. Archiving is not deletion. The business must agree retention periods and an authorised deletion procedure before launch; genuine erasure requires a maintainer to remove related records and private files consistently, taking backup retention and legal obligations into account. Never add a casual permanent-delete button to staff workflows.
