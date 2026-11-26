# The Chore Facilitator — V2 design review

Created 26 November 2026 as a separate direction. The original the-chore-facilitator folder is not modified by this build.

## Review
Open index.html, or run `node preview-server.cjs` from this folder and visit http://127.0.0.1:4317.
The preview server exposes public assets only; it does not execute or expose PHP source.

## Direction
People-led agency presentation inspired by the client-supplied Clementines and Top Au Pairs references: prominent real photography, immediate household/candidate routes, clear service navigation and a straightforward explanation of the placement process. All photography and branding come from the existing TCF project, not the reference agencies. No reference-agency testimonials, guarantees, contact details or candidate data were imported.

## Included
Home, About, Services, How it works, Placements, client brief, candidate application, Contact, Privacy, Cookies, Terms and 404 pages. Shared responsive styling, locally hosted fonts, keyboard/mobile navigation, back-to-top and the existing cookie preference controls.
Existing application, client and contact form markup/fields have been preserved. Supporting PHP/admin/database code is carried over, but this is NOT connected to the live database. server-config.php contains an explicitly disabled preview configuration and no live credentials.

## Preview controls
- Every HTML page has data-site-mode="preview". Form submission is disabled and visibly explained.
- Pages use noindex,nofollow; robots.txt disallows crawling.
- Optional analytics/marketing services are not installed.
- The sitemap and canonical URLs retain the intended production domain; these do not establish publication.

## Before any approved launch
Confirm the final design/content and service imagery with the client. Configure a separate xneelo staging environment if end-to-end testing is needed; do not casually point this review copy at the live database. Retain private storage outside the web root, then verify form receipt, admin access and mail delivery against the approved environment. Replace the disabled config using the existing setup documentation, enable production form mode deliberately, remove preview/noindex rules only on approval, and check live HTTPS, redirects, metadata and submission delivery.

## Verification
All 12 pages checked locally at 390px and 1440px. Main pages and forms additionally checked at 360px, 768px and 1024px. No horizontal overflow or broken images found. Internal page/anchor links checked. Menu open/Escape-close and cookie settings dialog checked. All three form blocks match the original field markup exactly and remain disabled in preview. No browser JavaScript exceptions observed. Automated text contrast check reported no issues on flat backgrounds. No live email, database or hosting verification was performed.
