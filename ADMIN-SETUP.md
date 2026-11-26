# The Chore Facilitator — staff setup

This is a PHP placement-management system, not a payroll engine. The public website keeps its existing design. GitHub Pages can preview the public pages only; it cannot run PHP, the database or staff login.

## Prerequisites

PHP 8.2+ with PDO MySQL, mbstring, fileinfo, sessions and JSON; MariaDB 10.6+ or MySQL 8; HTTPS; private writable account storage outside the web root. Tested versions and outstanding hosting checks are recorded in SECURITY-CHECKLIST.md. No Node server or Composer dependencies are required. The existing `.cjs` files are local/preview build tools, not production runtime files.

Follow DATABASE-SETUP.md and XNEELO-DEPLOYMENT.md first. The system fails closed until configuration is enabled, a private storage directory exists and a random application key is set.

## First Super Admin

There is no default account or password. With the private configuration available to PHP CLI, run the following on the hosting account over SSH (replace paths and the staff email/name):

```bash
export TCF_CONFIG_FILE=/usr/home/ACCOUNT/tcf-private/config.php
read -r -s -p 'New password (12–72 bytes): ' TCF_INITIAL_PASSWORD
printf '\n'
printf '%s\n' "$TCF_INITIAL_PASSWORD" | php /ACTUAL/WEB/ROOT/tools/create-super-admin.php 'staff@example.com' 'Staff Name'
unset TCF_INITIAL_PASSWORD
```

Use a unique password-manager-generated password. Do not put the password in arguments, SQL, source control or a public setup page. The script locks setup and refuses to create another initial account once any staff account exists. Remove `tools/create-super-admin.php` from the uploaded site after success. If SSH is unavailable, arrange a secure CLI run with the host; do not publish an installer.

Open `https://thechorefacilitator.co.za/admin/`. Super Admins can add staff through Users. Share initial credentials privately and have staff change them using My password. Disabling a user or changing permissions invalidates their other sessions on the next request. At least one active Super Admin is retained; self-demotion and self-disable are blocked.

## Daily workflow

1. Review new requests, applications and enquiries on the dashboard.
2. Open a client request to read/edit its questionnaire, change status and add private notes.
3. Review and verify applications. Choosing Approved creates a separate Available candidate profile; the public form never approves applicants.
4. Search candidates by name, role, status, area and availability. Add candidates for consideration from a request and progress their consideration status.
5. Create the placement from that consideration. Choose Direct Placement or Chore Payroll, a start date and a status. Confirmed/Active marks the candidate Placed and request Placement Confirmed. A candidate cannot have two confirmed/active placements. Closing a placement leaves availability for staff to review, rather than silently making the person available.
6. Notes and status history remain attached to records. Ordinary records are archived instead of deleted. Concurrent edits are rejected when a newer record version exists.

Super Admins alone manage staff, settings, audit history and private documents. Regular Admins manage operational records and notes. Documents are PDF/JPEG/PNG only, at most 8 MB each, stored outside the public root, downloaded through an authenticated endpoint and never rendered inline. Public applications have no document upload.

## Passwords and notifications

Password reset links expire after 30 minutes, are single-use and invalidate existing sessions. Only token hashes are stored; email links carry the token in a URL fragment to keep it out of normal access logs. JavaScript is required to open reset links. Real reset delivery must be tested on xneelo. If email is unavailable, existing Super Admins can create another authorised staff account; do not disable the last working account.

Public submissions commit to the database and notification queue before success. They remain saved if email is unavailable. Settings shows pending/failed notifications and permits retry; a cron worker performs delivery. A transport accepting a message does not prove inbox delivery. No private questionnaire answers or documents are included in notification emails.

All timestamps are stored/displayed as UTC. Readable references (CLI, REQ, APP, CAN, PLC, ENQ) are labels for numeric database IDs.
