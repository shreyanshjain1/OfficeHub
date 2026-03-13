# Office Request Hub (Procedural PHP + MySQL + Bootstrap 5 + Vanilla JS)

A production-ready internal web app for IT/Supplies/Office requests with attachments, approvals, assignments, status lifecycle, and an audit trail.

## Quick demo accounts (from seed)
- admin@office.local / Admin123!
- manager@office.local / Manager123!
- employee@office.local / Employee123!

---

# Install (Local)

## Requirements
- PHP 8.1+ (recommended 8.2+)
- MySQL 8+
- Apache/Nginx or PHP built-in server (for quick local dev)

## 1) Create DB + user
Create a MySQL database: `office_request_hub`

Create a DB user with least privileges to that DB only:
- SELECT, INSERT, UPDATE, DELETE
- CREATE, ALTER only during migrations (or run migrations with admin then downgrade privileges)

## 2) Configure env
Copy:
- `.env.example` → `.env`

Set:
- DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT
- APP_SECRET (long random)
- UPLOAD_DIR (absolute path recommended)

## 3) Run migrations + seeds
Run:
- `sql/001_schema.sql`
- `sql/002_seed.sql`
- (optional) `sql/003_testdata.sql`

## 4) Start server
If using PHP built-in server (local only):
- From project root:
  - `php -S localhost:8080 -t public`

Open:
- http://localhost:8080

---

# Install (cPanel / shared hosting)

## Recommended setup
- Set your domain/subdomain document root to: `/public`
- Ensure `UPLOAD_DIR` is OUTSIDE web root, e.g.:
  - `/home/<cpanel_user>/orh_storage/uploads`

## Steps
1. Upload the whole project
2. Set docroot to `/public` (or move contents of `/public` to docroot)
3. Create DB + user in cPanel → MySQL Databases
4. Import `sql/001_schema.sql`, then `sql/002_seed.sql`
5. Create `.env` in project root (same level as README.md)
6. Set:
   - `COOKIE_SECURE=1` (when HTTPS)
   - `UPLOAD_DIR=/home/<cpanel_user>/orh_storage/uploads`

---

# Security Implementation Map (where, how to verify, what gets logged)

## A) Auth & session (HttpOnly cookie, server-side session store)
**Where implemented**
- `app/auth.php`
  - `Auth::login()` creates DB session in `sessions` table
  - sets cookie `orh_sid` with HttpOnly + SameSite=Lax + Secure (if HTTPS)
- `app/security.php`
  - cookie flags + CSP/security headers
- `actions/logout.php` invalidates DB session (revoked_at)

**How to verify**
- DevTools → Application → Cookies:
  - `orh_sid` is HttpOnly ✅
  - Secure ✅ when HTTPS
  - SameSite=Lax ✅
- Logout → cookie cleared AND DB `sessions.revoked_at` set ✅
- Try to reuse cookie after logout → should be rejected (you’re redirected to login) ✅

**Logs / Audit**
- `audit_logs`:
  - LOGIN_OK / LOGIN_FAIL
  - LOGOUT

## Login rate limiting + uniform errors (no enumeration)
**Where**
- `app/rate_limit.php` + `Auth::login()` in `app/auth.php`

**Verify**
- Enter wrong password repeatedly:
  - You keep receiving same message “Invalid email or password” ✅
  - After threshold, you get blocked briefly (still same message) ✅

**Audit**
- LOGIN_FAIL (with email, ip), RATE_LIMIT bucket hits

---

## B) Authorization (RBAC + object-level policy; deny by default)
**Where**
- `app/policies.php` (Policy::canViewRequest, canEditRequest, canApproveRequest, canAssignRequest, canDownloadAttachment)
- Every sensitive page/action checks policy:
  - Request view: `app/views/requests/view.php`
  - Status change: `actions/request_status.php`
  - Assign: `actions/request_assign.php`
  - Comment: `actions/comment_add.php`
  - Download: `actions/download.php`
  - API list queries are scoped by role: `actions/api/requests_list.php`

**Verify (IDOR regression)**
- Login as employee.
- Open a request not owned by that employee (guess another ID).
- Expect: 403 Forbidden ✅
- Try download attachment from another user’s request:
  - Expect: 403 Forbidden ✅
- Manager: can only see department requests; other dept must be denied ✅

**Audit**
- AUTHZ_DENY entries for request/attachment/route

---

## C) Requests lifecycle + validation + mass assignment defense
**Where**
- `actions/request_create.php` validates and rejects unknown fields
- `actions/request_status.php` enforces lifecycle transitions
- `app/validator.php` (server-side validation helpers)

**Verify**
- Try missing required fields → rejected ✅
- Try sending extra POST fields (e.g., requester_user_id) → rejected ✅
- Try invalid transitions (e.g., CLOSED → OPEN) → rejected ✅

**Audit**
- REQUEST_CREATE, REQUEST_STATUS, VALIDATION_FAIL, AUTHZ_DENY

---

## D) Attachments (safe upload policy; stored outside web root; authorized downloads)
**Where**
- Upload policy: `app/uploads.php` + `actions/upload.php`
  - allow-list MIME: jpeg/png/pdf
  - max size 5MB
  - random stored name
  - sha256 recorded
- Storage location: `UPLOAD_DIR` (outside web root)
- Download controller: `actions/download.php` enforces authorization and forces attachment disposition for PDF

**Verify**
- Upload `.exe` or `.php` renamed: rejected (mime check via finfo) ✅
- Upload > 5MB: rejected ✅
- Confirm uploaded files are NOT in `/public` ✅
- Download:
  - requires being authorized to view the request ✅
  - PDF downloads as attachment ✅

**Audit**
- UPLOAD_OK / UPLOAD_REJECT / UPLOAD_FAIL
- DOWNLOAD_OK / DOWNLOAD_FAIL
- metadata includes request_id, mime, size

---

## E) Security headers (CSP, nosniff, referrer policy, frame deny, permissions policy, HSTS on HTTPS)
**Where**
- `app/security.php` applySecurityHeaders()
- `.htaccess` as defense-in-depth

**DevTools verification checklist**
- Open any page → DevTools → Network → select document → Headers:
  - X-Content-Type-Options: nosniff ✅
  - Referrer-Policy: strict-origin-when-cross-origin ✅
  - X-Frame-Options: DENY ✅
  - Permissions-Policy present ✅
  - Content-Security-Policy present ✅
  - Strict-Transport-Security present (HTTPS only) ✅

---

## F) Output encoding / XSS safety
**Where**
- `Security::e()` used across views
- No raw HTML from DB is rendered (only escaped + nl2br after escaping)

**Verify**
- Put `<script>alert(1)</script>` in title/description/comment
- It should display as text, not execute ✅

---

# Threat model notes (login + file upload + request viewing)

## Trust boundaries
1) Browser ↔ Server: untrusted user input, cookies, headers  
2) Server ↔ DB: only parameterized queries  
3) Uploads storage: must be outside web root; only server reads/writes  
4) Authorization boundary: request object ownership/department scoping  

## Top threats + mitigations
### Login
- Brute force → rate limits (per IP + per account), uniform errors
- Session hijacking → HttpOnly + SameSite + Secure (HTTPS), server-side sessions, bind session to IP+UA hashes, short TTL
- CSRF → CSRF token on all POST

### File upload
- Webshell / executable upload → allow-list MIME via finfo, random stored name, storage outside web root
- Oversized files → strict max size
- Unauthorized download → download controller checks Policy::canDownloadAttachment
- Content sniffing → nosniff + correct Content-Type + Content-Disposition attachment for PDF

### Request viewing (IDOR)
- Employee viewing others → Policy::canViewRequest; API query scoping by role
- Manager cross-department access → department scoping enforced at view + list
- Admin has full access → audited

---

# Endpoint security checklist template
For each endpoint:
- [ ] Auth required? (deny if not logged in)
- [ ] RBAC role check (if needed)
- [ ] Object-level policy check (Policy::*)
- [ ] CSRF for POST
- [ ] Rate limit (if abuse-prone)
- [ ] Validate fields + reject unknown fields
- [ ] Prepared statements only
- [ ] Audit log on write (and authz deny)
- [ ] Safe output encoding in views

---

# Release checklist template
- [ ] HTTPS enabled + COOKIE_SECURE=1
- [ ] UPLOAD_DIR outside web root and not publicly reachable
- [ ] DB user has least privileges
- [ ] Backups enabled (DB + uploads)
- [ ] Headers verified (CSP, HSTS on HTTPS, nosniff)
- [ ] Audit logs writing correctly
- [ ] IDOR tests pass (employee cannot view others)
- [ ] Upload rejection tests pass (bad mime / oversize)

---

# Test plan + automated tests

This project includes a lightweight PHP test runner (no Composer).

## Run tests
From project root:
- `php tests/run.php http://localhost:8080`

---

